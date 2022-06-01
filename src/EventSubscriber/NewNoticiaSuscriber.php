<?php
namespace Drupal\simple_facebook_post\EventSubscriber;

use Drupal\entity_events\Event\EntityEvent;
use Drupal\entity_events\EventSubscriber\EntityEventInsertSubscriber;
use Drupal\simple_facebook_post\Utils\SocialPost;

class NewNoticiaSuscriber extends EntityEventInsertSubscriber {

  public function onEntityInsert(EntityEvent $event) {
    
    $config_manager = \Drupal::service('simple_facebook_post.config_manager');
    $content_type   = $config_manager::get('content');
    $social_publish = $config_manager::get('publish_field');
    $entity         = $event->getEntity();

    if ($entity instanceof \Drupal\node\NodeInterface) {

      /**
       * @var \Drupal\node\NodeInterface $entity  
       * Se trata de una entidad Node
       */
      if($entity->bundle() == $content_type && isset($entity->{$social_publish})){
        
        $publish = $entity->{$social_publish}->value;
        if($publish->value === ''){
          
          $post = new SocialPost($entity);
        }
      }
    }
  }
}