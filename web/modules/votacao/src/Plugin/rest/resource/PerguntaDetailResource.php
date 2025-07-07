<?php

declare(strict_types=1);

namespace Drupal\votacao\Plugin\rest\resource;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\rest\Attribute\RestResource;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

#[RestResource(
  id: 'votacao_pergunta_detail_api',
  label: new TranslatableMarkup('Pergunta Detail API'),
  uri_paths: [
    'canonical' => '/api/pergunta/{id}'
  ],
)]
final class PerguntaDetailResource extends ResourceBase {

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

  public function get(int $id, Request $request): ResourceResponse {
    $globalDisabled = $this->configFactory->get('votacao.settings')->get('disable');
    if ($globalDisabled) {
      throw new AccessDeniedHttpException("O sistema de votação está temporariamente desativado.");
    }
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
      throw new AccessDeniedHttpException("Pergunta desativada.");
    }

    $opcoes = array_map(function($opcao) {
      /** @var \Drupal\votacao\Entity\Resposta $opcao */
      return [
        'id' => $opcao->id(),
        'titulo' => $opcao->label(),
        'descricao' => $opcao->get('description')->value,
        'imagem_url' => $opcao->get('imagem')->entity?->createFileUrl(FALSE) ?? NULL,
        'votos' => (int) $opcao->get('votos')->value,
      ];
    }, $pergunta->get('opcoes')->referencedEntities());

    $data = [
      'id' => $pergunta->id(),
      'titulo' => $pergunta->label(),
      'show_results' => (bool) $pergunta->get('show_results')->value,
      'opcoes' => $opcoes,
    ];

    $response = new ResourceResponse($data);
    $response->addCacheableDependency($pergunta);
    $response->setMaxAge(60); // 60 seg de cache
    return $response;
  }
}
