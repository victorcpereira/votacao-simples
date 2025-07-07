<?php

namespace Drupal\votacao\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Pager\PagerManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class ResultadosController extends ControllerBase {

  protected PagerManagerInterface $pagerManager;

  public function __construct(PagerManagerInterface $pagerManager) {
    $this->pagerManager = $pagerManager;
  }

  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('pager.manager')
    );
  }

  public function listar(): array {
    $limit = 10;
    $storage = $this->entityTypeManager()->getStorage('vtc_pergunta');
    $total = $storage->getQuery()
      ->accessCheck(TRUE)
      ->count()
      ->execute();

    $page = (int) \Drupal::request()->query->get('page', 0);
    $this->pagerManager->createPager($total, $limit);

    $ids = $storage->getQuery()
      ->accessCheck(TRUE)
      ->sort('id', 'ASC')
      ->range($page * $limit, $limit)
      ->execute();

    $perguntas = $storage->loadMultiple($ids);

    $build = [];

    foreach ($perguntas as $pergunta) {
      $opcoes = $pergunta->get('opcoes')->referencedEntities();
      $totalVotos = array_reduce($opcoes, fn($carry, $opcao) => $carry + (int) $opcao->get('votos')->value, 0);

      $rows = [];
      foreach ($opcoes as $opcao) {
        $votos = (int) $opcao->get('votos')->value;
        $percentual = $totalVotos > 0 ? number_format(($votos / $totalVotos) * 100, 1) . '%' : '0%';

        $rows[] = [
          'data' => [
            $opcao->label(),
            $votos,
            $percentual,
          ],
        ];
      }

      $rows[] = [
        'data' => [
          ['data' => $this->t('Total'), 'colspan' => 1, 'header' => TRUE],
          ['data' => $totalVotos, 'colspan' => 2],
        ],
        'class' => ['total-row'],
      ];

      $build[] = [
        '#type' => 'html_tag',
        '#tag' => 'h3',
        '#value' => $pergunta->label(),
      ];

      $build[] = [
        '#type' => 'table',
        '#header' => ['Opção', 'Votos', 'Porcentagem'],
        '#rows' => $rows,
        '#empty' => $this->t('Nenhuma opção registrada.'),
      ];
    }

    $build['pager'] = [
      '#type' => 'pager',
    ];

    return $build;
  }

}
