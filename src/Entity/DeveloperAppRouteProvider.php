<?php

/**
 * Copyright 2018 Google Inc.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * version 2 as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 * MA 02110-1301, USA.
 */

namespace Drupal\apigee_edge\Entity;

use Drupal\apigee_edge\Access\MyAppsAccessCheck;
use Drupal\apigee_edge\Controller\DeveloperAppViewControllerForDeveloper;
use Drupal\apigee_edge\Entity\ListBuilder\DeveloperAppListBuilderForDeveloper;
use Drupal\apigee_edge\Form\DeveloperAppAnalyticsFormForDeveloper;
use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\Routing\Route;

/**
 * Default entity routes for developer apps.
 */
class DeveloperAppRouteProvider extends AppRouteProvider {

  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $collection = parent::getRoutes($entity_type);
    $entity_type_id = $entity_type->id();

    if ($collection_by_developer = $this->getCollectionRouteByDeveloper($entity_type)) {
      $collection->add("entity.{$entity_type_id}.collection_by_developer", $collection_by_developer);
    }

    if ($canonical_by_developer = $this->getCanonicalRouteByDeveloper($entity_type)) {
      $collection->add("entity.{$entity_type_id}.canonical_by_developer", $canonical_by_developer);
    }

    if ($add_form_for_developer = $this->getAddFormRouteForDeveloper($entity_type)) {
      $collection->add("entity.{$entity_type_id}.add_form_for_developer", $add_form_for_developer);
    }

    if ($edit_form_for_developer = $this->getEditFormRouteForDeveloper($entity_type)) {
      $collection->add("entity.{$entity_type_id}.edit_form_for_developer", $edit_form_for_developer);
    }

    if ($delete_form_for_developer = $this->getDeleteFormRouteForDeveloper($entity_type)) {
      $collection->add("entity.{$entity_type_id}.delete_form_for_developer", $delete_form_for_developer);
    }

    if ($analytics_for_developer = $this->getAnalyticsRouteForDeveloper($entity_type)) {
      $collection->add("entity.{$entity_type_id}.analytics_for_developer", $analytics_for_developer);
    }

    return $collection;
  }

  /**
   * {@inheritdoc}
   */
  protected function getAddFormRoute(EntityTypeInterface $entity_type) {
    $route = parent::getAddFormRoute($entity_type);
    if ($route) {
      // We did not want to expose this UI to regular users this the reason
      // why route is only available to admins for now.
      $route->setRequirement('_permission', 'administer developer_app');
    }

    return $route;
  }

  /**
   * {@inheritdoc}
   */
  protected function getCollectionRoute(EntityTypeInterface $entity_type) {
    $route = parent::getCollectionRoute($entity_type);
    if ($route) {
      // Add "access developer_app overview" to the autogenerated permission
      // requirement" (that contains "administer developer_app").
      $route->setRequirement('_permission', $route->getRequirement('_permission') . '+access developer_app overview');
    }

    return $route;
  }

  /**
   * Gets the add-form route for developer.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getAddFormRouteForDeveloper(EntityTypeInterface $entity_type) {
    if ($entity_type->hasLinkTemplate('add-form-for-developer')) {
      $route = new Route($entity_type->getLinkTemplate('add-form-for-developer'));
      $route->setDefault('_entity_form', 'developer_app.add_for_developer');
      $route->setDefault('_title_callback', AppTitleProvider::class . '::addTitle');
      $route->setDefault('entity_type_id', $entity_type->id());
      if (strpos($route->getPath(), '{user}') !== FALSE) {
        $route->setRequirement('user', '\d+');
      }
      $route->setRequirement('_entity_create_access', $entity_type->id());
      return $route;
    }
  }

  /**
   * Gets the edit-form route for a developer.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getEditFormRouteForDeveloper(EntityTypeInterface $entity_type) {
    if ($entity_type->hasLinkTemplate('edit-form-for-developer')) {
      $route = new Route($entity_type->getLinkTemplate('edit-form-for-developer'));
      $route->setDefault('_entity_form', 'developer_app.edit_for_developer');
      $route->setDefault('_title_callback', AppTitleProvider::class . '::editTitle');
      $route->setDefault('entity_type_id', $entity_type->id());
      if (strpos($route->getPath(), '{user}') !== FALSE) {
        $route->setRequirement('user', '\d+');
      }
      $route->setRequirement('_developer_app_access', 'update');
      // We must load the entity from Apigee Edge directly and omit cached
      // version on edit forms.
      $route->setOption('apigee_edge_load_unchanged_entity', 'true');
      return $route;
    }
  }

  /**
   * Gets the delete-form route for a developer.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getDeleteFormRouteForDeveloper(EntityTypeInterface $entity_type) {
    if ($entity_type->hasLinkTemplate('delete-form-for-developer')) {
      $route = new Route($entity_type->getLinkTemplate('delete-form-for-developer'));
      $route->setDefault('_entity_form', 'developer_app.delete_for_developer');
      $route->setDefault('_title_callback', AppTitleProvider::class . '::deleteTitle');
      $route->setDefault('entity_type_id', $entity_type->id());
      if (strpos($route->getPath(), '{user}') !== FALSE) {
        $route->setRequirement('user', '\d+');
      }
      $route->setRequirement('_developer_app_access', 'delete');
      return $route;
    }
  }

  /**
   * Gets the canonical route for a developer.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getCanonicalRouteByDeveloper(EntityTypeInterface $entity_type) {
    if ($entity_type->hasLinkTemplate('canonical-by-developer')) {
      $route = new Route($entity_type->getLinkTemplate('canonical-by-developer'));
      $route->setDefault('_controller', DeveloperAppViewControllerForDeveloper::class . '::view');
      $route->setDefault('_title_callback', AppTitleProvider::class . ':title');
      $route->setDefault('entity_type_id', $entity_type->id());
      if (strpos($route->getPath(), '{user}') !== FALSE) {
        $route->setRequirement('user', '\d+');
      }
      $route->setRequirement('_developer_app_access', 'view');
      return $route;
    }
  }

  /**
   * Gets the collection route for a developer.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getCollectionRouteByDeveloper(EntityTypeInterface $entity_type) {
    if ($entity_type->hasLinkTemplate('collection-by-developer')) {
      $route = new Route($entity_type->getLinkTemplate('collection-by-developer'));
      $route->setDefault('_controller', DeveloperAppListBuilderForDeveloper::class . '::render');
      $route->setDefault('_title_callback', 'apigee_edge_get_my_developer_apps_title');
      $route->setDefault('entity_type_id', $entity_type->id());
      if (strpos($route->getPath(), '{user}') !== FALSE) {
        $route->setRequirement('user', '\d+');
      }
      $route->setRequirement('_custom_access', MyAppsAccessCheck::class . '::access');
      return $route;
    }
  }

  /**
   * Gets the app analytics route for a developer.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getAnalyticsRouteForDeveloper(EntityTypeInterface $entity_type) {
    if ($entity_type->hasLinkTemplate('analytics-for-developer')) {
      $route = new Route($entity_type->getLinkTemplate('analytics-for-developer'));
      $route->setDefault('_form', DeveloperAppAnalyticsFormForDeveloper::class);
      $route->setDefault('_title_callback', AppTitleProvider::class . '::analyticsTitle');
      $route->setDefault('entity_type_id', $entity_type->id());
      if (strpos($route->getPath(), '{user}') !== FALSE) {
        $route->setRequirement('user', '\d+');
      }
      $route->setRequirement('_developer_app_access', 'analytics');
      return $route;
    }
  }

}
