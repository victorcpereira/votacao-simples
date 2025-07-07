<?php

declare(strict_types=1);

namespace Drupal\votacao\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Votação settings for this site.
 */
final class VotacaoSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'votacao_votacao_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['votacao.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('votacao.settings');

    $form['api_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Token da API'),
      '#default_value' => $config->get('api_token'),
      '#required' => TRUE,
    ];

    $form['disable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Desativar o sistema de votação'),
      '#default_value' => $config->get('disable'),
      '#description' => $this->t('Se marcado, nenhum voto poderá ser registrado temporariamente.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->configFactory->getEditable('votacao.settings')
      ->set('api_token', $form_state->getValue('api_token'))
      ->set('disable', $form_state->getValue('disable'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
