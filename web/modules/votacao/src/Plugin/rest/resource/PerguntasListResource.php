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

#[RestResource(
  id: 'votacao_perguntas_list_api',
  label: new TranslatableMarkup('Perguntas List API'),
  uri_paths: [
    'canonical' => '/api/perguntas'
  ],
)]
final class PerguntasListResource extends ResourceBase {

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

  public function get(): ResourceResponse {
    $storage = $this->entityTypeManager->getStorage('vtc_pergunta');
    $query = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('status', true)
      ->sort('id', 'ASC');

    // Paginação: 10 itens por página
    $page = (int) ($_GET['page'] ?? 0);
    $limit = 10;
    $query->range($page * $limit, $limit);

    $ids = $query->execute();
    $entities = $storage->loadMultiple($ids);

    $data = [];
    foreach ($entities as $pergunta) {
      $data[] = [
        'id' => $pergunta->id(),
        'titulo' => $pergunta->get('label')->value,
        'enabled' => (bool) $pergunta->get('status')->value,
        'show_results' => (bool) $pergunta->get('show_results')->value,
      ];
    }

    return new ResourceResponse([
      'page' => $page,
      'limit' => $limit,
      'count' => count($data),
      'items' => $data,
    ]);
  }
}
