<?php

declare(strict_types=1);

namespace Drupal\votacao;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a pergunta entity type.
 */
interface PerguntaInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
