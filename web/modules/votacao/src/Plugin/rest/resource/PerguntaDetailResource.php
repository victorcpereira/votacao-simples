<?php

declare(strict_types=1);

namespace Drupal\votacao\Plugin\rest\resource;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\rest\Attribute\RestResource;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

#[RestResource(
  id: 'votacao_pergunta_detail_api',
  label: new TranslatableMarkup('Pergunta Detail API'),
  uri_paths: [
    'canonical' => '/api/pergunta/{id}',
  ],
)]
final class PerguntaDetailResource extends ResourceBase {

  protected EntityTypeManagerInterface $entityTypeManager;

  protected AccountProxyInterface $currentUser;

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    AccountProxyInterface $current_user,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new self(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('rest'),
      $container->get('current_user'),
      $container->get('entity_type.manager')
    );
  }

  public function get(int $id): ResourceResponse {
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
        'titulo' => $opcao->get('label')->value,
        'descricao' => $opcao->get('description')->value,
        'imagem_url' => $opcao->get('imagem')->entity?->createFileUrl(FALSE) ?? NULL,
        'votos' => (int) $opcao->get('votos')->value,
      ];
    }, $pergunta->get('opcoes')->referencedEntities());

    $data = [
      'id' => $pergunta->id(),
      'titulo' => $pergunta->get('label')->value,
      'show_results' => (bool) $pergunta->get('show_results')->value,
      'opcoes' => $opcoes,
    ];

    return new ResourceResponse($data);
  }

}
