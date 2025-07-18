<?php

declare(strict_types=1);

namespace Drupal\votacao;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Provides a list controller for the pergunta entity type.
 */
final class PerguntaListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['id'] = $this->t('ID');
    $header['label'] = $this->t('Label');
    $header['status'] = $this->t('Status');
    $header['uid'] = $this->t('Author');
    $header['created'] = $this->t('Created');
    $header['changed'] = $this->t('Updated');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\votacao\PerguntaInterface $entity */
    $row['id'] = $entity->id();
    $row['label'] = Link::createFromRoute($entity->label(), 'votacao.pergunta', ['vtc_pergunta' => $entity->id()]);
    $row['status'] = $entity->get('status')->value ? $this->t('Enabled') : $this->t('Disabled');
    $username_options = [
      'label' => 'hidden',
      'settings' => ['link' => $entity->get('uid')->entity->isAuthenticated()],
    ];
    $row['uid']['data'] = $entity->get('uid')->view($username_options);
    $row['created']['data'] = $entity->get('created')
      ->view(['label' => 'hidden']);
    $row['changed']['data'] = $entity->get('changed')
      ->view(['label' => 'hidden']);
    return $row + parent::buildRow($entity);
  }

}
