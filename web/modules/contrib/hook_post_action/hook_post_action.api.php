<?php

/**
 * @file
 * Documents API functions for hook_post_action module.
 */

use Drupal\Core\Entity\EntityInterface;

/**
 * Gets called after an entity has been inserted/updated/deleted to database.
 *
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   An entity object.
 * @param string $op
 *   An string containing the operating that's taking place (insert/update/delete)
 *
 * @see hook_entity_postinsert()
 * @see hook_entity_postupdate()
 * @see hook_entity_postdelete()
 *
 * @ingroup entity_crud
 */
function hook_entity_postsave(EntityInterface $entity, $op) {
  $id = $entity->id();
  $entity_type = $entity->getEntityTypeId();

  \Drupal::logger('hook_post_action_test')
    ->info('The @op entity @entity_type id is @id from @function.', [
      '@op' => t($op . 'd'),
      '@id' => $id,
      '@function' => __FUNCTION__,
      '@entity_type' => $entity_type,
    ]);
}

/**
 * Gets called after an entity has been inserted to database.
 *
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   An entity object.
 *
 * @see hook_entity_postsave()
 * @see hook_entity_postupdate()
 * @see hook_entity_postdelete()
 *
 * @ingroup entity_crud
 */
function hook_entity_postinsert(EntityInterface $entity) {
  $id = $entity->id();
  $entity_type = $entity->getEntityTypeId();

  \Drupal::logger('hook_post_action_test')
    ->info('The inserted entity @entity_type id is @id from @function.', [
      '@id' => $id,
      '@function' => __FUNCTION__,
      '@entity_type' => $entity_type,
    ]);
}

/**
 * Gets called after an entity has been updated in database.
 *
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   An entity object.
 *
 * @see hook_entity_postsave()
 * @see hook_entity_postinsert()
 * @see hook_entity_postdelete()
 *
 * @ingroup entity_crud
 */
function hook_entity_postupdate(EntityInterface $entity) {
  $id = $entity->id();
  $entity_type = $entity->getEntityTypeId();

  \Drupal::logger('hook_post_action_test')
    ->info('The updated entity @entity_type id is @id from @function.', [
      '@id' => $id,
      '@function' => __FUNCTION__,
      '@entity_type' => $entity_type,
    ]);
}

/**
 * Gets called after an entity has been deleted from database.
 *
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   An entity object.
 *
 * @see hook_entity_postsave()
 * @see hook_entity_postinsert()
 * @see hook_entity_postupdate()
 *
 * @ingroup entity_crud
 */
function hook_entity_postdelete(EntityInterface $entity) {
  $id = $entity->id();
  $entity_type = $entity->getEntityTypeId();

  \Drupal::logger('hook_post_action_test')
    ->info('The deleted entity @entity_type id is @id from @function.', [
      '@id' => $id,
      '@function' => __FUNCTION__,
      '@entity_type' => $entity_type,
    ]);
}

/**
 * Gets called after content entity operation has been completed.
 *
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   An entity object.
 * @param string $op
 *   An string containing the operating that's taking place.
 *
 * @see hook_ENTITY_TYPE_postinsert()
 * @see hook_ENTITY_TYPE_postupdate()
 * @see hook_ENTITY_TYPE_postdelete()
 *
 * @ingroup entity_crud
 */
function hook_ENTITY_TYPE_postsave(EntityInterface $entity, $op) {
  $id = $entity->id();
  $entity_type = $entity->getEntityTypeId();
  $bundle = $entity->bundle();

  \Drupal::logger('hook_post_action_test')
    ->info('The @op @entity_type @bundle id is @id from @function.', [
      '@op' => t($op . 'd'),
      '@id' => $id,
      '@bundle' => $bundle,
      '@function' => __FUNCTION__,
      '@entity_type' => $entity_type,
    ]);
}

/**
 * Gets called after content entity has been inserted to database.
 *
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   An entity object.
 *
 * @see hook_ENTITY_TYPE_postsave()
 * @see hook_ENTITY_TYPE_postupdate()
 * @see hook_ENTITY_TYPE_postdelete()
 *
 * @ingroup entity_crud
 */
function hook_ENTITY_TYPE_postinsert(EntityInterface $entity) {
  $id = $entity->id();
  $entity_type = $entity->getEntityTypeId();
  $bundle = $entity->bundle();

  \Drupal::logger('hook_post_action_test')
    ->info('The inserted @entity_type @bundle id is @id from @function.', [
      '@id' => $id,
      '@bundle' => $bundle,
      '@function' => __FUNCTION__,
      '@entity_type' => $entity_type,
    ]);
}

/**
 * Gets called after content entity has been updated to database.
 *
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   An entity object.
 *
 * @see hook_ENTITY_TYPE_postsave()
 * @see hook_ENTITY_TYPE_postinsert()
 * @see hook_ENTITY_TYPE_postdelete()
 *
 * @ingroup entity_crud
 */
function hook_ENTITY_TYPE_postupdate(EntityInterface $entity) {
  $id = $entity->id();
  $entity_type = $entity->getEntityTypeId();
  $bundle = $entity->bundle();

  \Drupal::logger('hook_post_action_test')
    ->info('The updated @entity_type @bundle id is @id from @function.', [
      '@id' => $id,
      '@bundle' => $bundle,
      '@function' => __FUNCTION__,
      '@entity_type' => $entity_type,
    ]);
}

/**
 * Gets called after content entity has been deleted from database.
 *
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   An entity object.
 *
 * @see hook_ENTITY_TYPE_postsave()
 * @see hook_ENTITY_TYPE_postinsert()
 * @see hook_ENTITY_TYPE_postupdate()
 * 
 * @ingroup entity_crud
 */
function hook_ENTITY_TYPE_postdelete(EntityInterface $entity) {
  $id = $entity->id();
  $entity_type = $entity->getEntityTypeId();
  $bundle = $entity->bundle();

  \Drupal::logger('hook_post_action_test')
    ->info('The deleted @entity_type @bundle id is @id from @function.', [
      '@id' => $id,
      '@bundle' => $bundle,
      '@function' => __FUNCTION__,
      '@entity_type' => $entity_type,
    ]);
}
