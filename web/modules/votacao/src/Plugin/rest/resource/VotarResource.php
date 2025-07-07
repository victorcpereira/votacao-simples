<?php

declare(strict_types=1);

namespace Drupal\votacao\Plugin\rest\resource;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\rest\Attribute\RestResource;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

#[RestResource(
  id: 'votacao_votar_api',
  label: new TranslatableMarkup('Votar API'),
  uri_paths: [
    'create' => '/api/pergunta/{id}/votar'
  ]
)]
final class VotarResource extends ResourceBase {

  protected EntityTypeManagerInterface $entityTypeManager;
  protected AccountProxyInterface $currentUser;
  protected ConfigFactoryInterface $configFactory;

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    AccountProxyInterface $current_user,
    EntityTypeManagerInterface $entity_type_manager,
    ConfigFactoryInterface $config_factory
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
    $this->configFactory = $config_factory;
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new self(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('rest'),
      $container->get('current_user'),
      $container->get('entity_type.manager'),
      $container->get('config.factory')
    );
  }

  public function post(int $id, array $data, Request $request): ModifiedResourceResponse {
    // Verificação de token via configuração do sistema
    $clientToken = $request->headers->get('X-API-TOKEN');
    $expectedToken = $this->configFactory->get('votacao.settings')->get('api_token');

    if (!$expectedToken || $clientToken !== $expectedToken) {
      throw new AccessDeniedHttpException("Token de acesso inválido ou ausente.");
    }

    $storage = $this->entityTypeManager->getStorage('vtc_pergunta');
    $pergunta = $storage->load($id);

    if (!$pergunta) {
      throw new NotFoundHttpException("Pergunta com ID $id não encontrada.");
    }

    if (!$pergunta->get('status')->value) {
      throw new AccessDeniedHttpException("Votação desativada.");
    }

    $opcao_id = $data['opcao_id'] ?? null;
    if (!$opcao_id || !is_numeric($opcao_id)) {
      throw new BadRequestHttpException("ID da opção de resposta é obrigatório.");
    }

    $opcoes = $pergunta->get('opcoes')->referencedEntities();
    $opcao = null;

    foreach ($opcoes as $item) {
      if ((int) $item->id() === (int) $opcao_id) {
        $opcao = $item;
        break;
      }
    }

    if (!$opcao) {
      throw new BadRequestHttpException("A opção informada não pertence à pergunta.");
    }

    $votos = (int) $opcao->get('votos')->value;
    $opcao->set('votos', $votos + 1);
    $opcao->save();

    return new ModifiedResourceResponse([
      'message' => 'Voto registrado com sucesso.',
      'opcao_id' => $opcao->id(),
      'total_votos' => $opcao->get('votos')->value,
    ], 200);
  }
}
