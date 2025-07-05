<?php

declare(strict_types=1);

namespace Drupal\votacao\Entity;

use Drupal\Core\Entity\Attribute\ContentEntityType;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Form\DeleteMultipleForm;
use Drupal\Core\Entity\Routing\AdminHtmlRouteProvider;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\user\EntityOwnerTrait;
use Drupal\views\EntityViewsData;
use Drupal\votacao\Form\PerguntaForm;
use Drupal\votacao\PerguntaInterface;
use Drupal\votacao\PerguntaListBuilder;

/**
 * Defines the pergunta entity class.
 */
#[ContentEntityType(
  id: 'vtc_pergunta',
  label: new TranslatableMarkup('Pergunta'),
  label_collection: new TranslatableMarkup('Perguntas'),
  label_singular: new TranslatableMarkup('pergunta'),
  label_plural: new TranslatableMarkup('perguntas'),
  entity_keys: [
    'id' => 'id',
    'label' => 'label',
    'owner' => 'uid',
    'published' => 'status',
    'uuid' => 'uuid',
  ],
  handlers: [
    'list_builder' => PerguntaListBuilder::class,
    'views_data' => EntityViewsData::class,
    'form' => [
      'add' => PerguntaForm::class,
      'edit' => PerguntaForm::class,
      'delete' => ContentEntityDeleteForm::class,
      'delete-multiple-confirm' => DeleteMultipleForm::class,
    ],
    'route_provider' => [
      'html' => AdminHtmlRouteProvider::class,
    ],
  ],
  links: [
    'collection' => '/admin/content/vtc-pergunta',
    'add-form' => '/vtc-pergunta/add',
    'canonical' => '/vtc-pergunta/{vtc_pergunta}',
    'edit-form' => '/vtc-pergunta/{vtc_pergunta}/edit',
    'delete-form' => '/vtc-pergunta/{vtc_pergunta}/delete',
    'delete-multiple-form' => '/admin/content/vtc-pergunta/delete-multiple',
  ],
  admin_permission: 'administer vtc_pergunta',
  base_table: 'vtc_pergunta',
  label_count: [
    'singular' => '@count perguntas',
    'plural' => '@count perguntas',
  ],
  field_ui_base_route: 'entity.vtc_pergunta.settings',
)]
class Pergunta extends ContentEntityBase implements PerguntaInterface
{

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage): void
  {
    parent::preSave($storage);
    if (!$this->getOwnerId()) {
      // If no owner has been set explicitly, make the anonymous user the owner.
      $this->setOwnerId(0);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
  {

    $fields = parent::baseFieldDefinitions($entity_type);

    // Campo Label
    $fields['label'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Label'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('view', TRUE);

    // Campo Status
    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Status'))
      ->setDefaultValue(TRUE)
      ->setSetting('on_label', t('Published'))
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'settings' => [
          'display_label' => FALSE,
        ],
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'boolean',
        'label' => 'above',
        'weight' => 0,
        'settings' => [
          'format' => 'enabled-disabled',
        ],
      ])
      ->setDisplayConfigurable('view', TRUE);

    // Mostrar resultados após o voto
    $fields['show_results'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Mostrar resultados após o voto'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 10,
      ]);

    // Campo Opções
    $fields['opcoes'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Opções de resposta'))
      ->setDescription(t('Selecione as opções de resposta disponíveis para esta pergunta.'))
      ->setSetting('target_type', 'vtc_resposta')
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setDisplayOptions('form', [
        'type' => 'inline_entity_form_complex',
        'weight' => 30,
        'settings' => [
          'override_labels' => TRUE,
          'label_singular' => 'Opção de Resposta',
          'label_plural' => 'Opções de Resposta',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);


    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Author'))
      ->setSetting('target_type', 'user')
      ->setDefaultValueCallback(self::class . '::getDefaultEntityOwner')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => '',
        ],
        'weight' => 15,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'author',
        'weight' => 15,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Authored on'))
      ->setDescription(t('The time that the pergunta was created.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'timestamp',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the pergunta was last edited.'));

    return $fields;
  }

}
