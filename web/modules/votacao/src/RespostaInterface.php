<?php

declare(strict_types=1);

namespace Drupal\votacao;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a resposta entity type.
 */
interface RespostaInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
