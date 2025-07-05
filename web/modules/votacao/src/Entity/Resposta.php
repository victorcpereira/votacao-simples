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
use Drupal\votacao\Form\RespostaForm;
use Drupal\votacao\RespostaInterface;
use Drupal\votacao\RespostaListBuilder;

/**
 * Defines the resposta entity class.
 */
#[ContentEntityType(
  id: 'vtc_resposta',
  label: new TranslatableMarkup('Resposta'),
  label_collection: new TranslatableMarkup('Respostas'),
  label_singular: new TranslatableMarkup('resposta'),
  label_plural: new TranslatableMarkup('respostas'),
  entity_keys: [
    'id' => 'id',
    'label' => 'label',
    'owner' => 'uid',
    'published' => 'status',
    'uuid' => 'uuid',
  ],
  handlers: [
    'list_builder' => RespostaListBuilder::class,
    'views_data' => EntityViewsData::class,
    'form' => [
      'add' => RespostaForm::class,
      'edit' => RespostaForm::class,
      'delete' => ContentEntityDeleteForm::class,
      'delete-multiple-confirm' => DeleteMultipleForm::class,
    ],
    'route_provider' => [
      'html' => AdminHtmlRouteProvider::class,
    ],
  ],
  links: [
    'collection' => '/admin/content/vtc-resposta',
    'add-form' => '/vtc-resposta/add',
    'canonical' => '/vtc-resposta/{vtc_resposta}',
    'edit-form' => '/vtc-resposta/{vtc_resposta}',
    'delete-form' => '/vtc-resposta/{vtc_resposta}/delete',
    'delete-multiple-form' => '/admin/content/vtc-resposta/delete-multiple',
  ],
  admin_permission: 'administer vtc_resposta',
  base_table: 'vtc_resposta',
  label_count: [
    'singular' => '@count respostas',
    'plural' => '@count respostas',
  ],
  field_ui_base_route: 'entity.vtc_resposta.settings',
)]
class Resposta extends ContentEntityBase implements RespostaInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage): void {
    parent::preSave($storage);
    if (!$this->getOwnerId()) {
      // If no owner has been set explicitly, make the anonymous user the owner.
      $this->setOwnerId(0);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {

    $fields = parent::baseFieldDefinitions($entity_type);

    // Título da opção
    $fields['label'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Título da opção'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 0,
      ]);

    // Descrição
    $fields['description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Description'))
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'text_default',
        'label' => 'above',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('view', TRUE);

    // Imagem
    $fields['imagem'] = BaseFieldDefinition::create('image')
      ->setLabel(t('Imagem'))
      ->setDisplayOptions('form', [
        'type' => 'image_image',
        'weight' => 2,
      ]);

    // Número de votos
    $fields['votos'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Total de votos'))
      ->setDefaultValue(0)
      ->setReadOnly(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 3,
      ]);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Status'))
      ->setDefaultValue(TRUE)
      ->setSetting('on_label', t('Enabled'))
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
      ->setDescription(t('The time that the resposta was created.'))
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
      ->setDescription(t('The time that the resposta was last edited.'));

    return $fields;
  }

}
