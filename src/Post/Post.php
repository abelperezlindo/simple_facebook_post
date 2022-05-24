<?php 
namespace Drupal\analisis_autopost\Post;

use \Drupal\Component\Utility\Html;
use Drupal\file\Entity\File;
use Drupal\Core\Url;

//https://developers.facebook.com/docs/graph-api/reference/v6.0/page/feed 
class Post{
  
  /** @var string $message Contiene el texto del posteo El mensaje puede contener menciones de páginas de Facebook*/
  private $message;
  /** @var string $link Contiene el link al sitio web del posteo */
  private $link;
  /** @var string $name  */
  private $name;
  /** @var string $source */
  private $source;

  /**
   * Get the value of source
   */ 
  public function __construct(array $options = null)
  {
    if(is_null($options)) return;

    $this->message  = $options['message'] ?? '';
    $this->link     = $options['link'] ?? '';
    $this->name     = $options['name'] ?? '';
    $this->source   = $options['source'] ?? '';

  }
  /**
   * Carga el Post con la informacion de una entidad nodo
   *
   * @param \Drupal\node\NodeInterface $node
   * @return void
   */
  public function setUpFromEntity(\Drupal\node\NodeInterface $node){
      
    $title    = $node->title->getValue();
    $body     = $node->body->getValue();
    $image_url    = $node->field_imagenprincipal->getValue();

    if(empty($image_url)){
      $image_url = '';
    } else {
      // Obtener desde el field imagen la url de la imagen
      $target = $image_url[0]['target_id'];
      $file = File::load($target);
      $file_uri = $file->getFileUri();
      $image_url = file_create_url($file_uri);

    }
    /** if body not set scape */
    if(empty($title) || empty($body)) return false;

    if(empty($body[0]['summary'])){
      $summary = Html::decodeEntities($body[0]['body']);
    } else {
      $summary = Html::decodeEntities($body[0]['summary']);
    }

    /** @var \Drupal\Core\Url $url */
    $node_url      = $node->toUrl();
    $node_url->setAbsolute(TRUE);


    // set up
    $this->message  = $summary;
    $this->name     = Html::decodeEntities($title) . ' | Análisis' ;
    $this->source   = $image_url;
    $this->link     = $node_url->toString();
  }

  /**  */
  public function isPosteable(){
    if(!empty($this->message) && !empty($this->link)){
      return true;
    }
    return false;
  }
  public function getSource()
  {
    return $this->source;
  }

  /**
   * Set the value of source
   *
   * @return  self
   */ 
  public function setSource($source)
  {
    $this->source = $source;

    return $this;
  }

  /**
   * Get the value of name
   */ 
  public function getName()
  {
    return $this->name;
  }

  /**
   * Set the value of name
   *
   * @return  self
   */ 
  public function setName($name)
  {
    $this->name = $name;

    return $this;
  }

  /**
   * Get the value of link
   */ 
  public function getLink()
  {
    return $this->link;
  }

  /**
   * Set the value of link
   *
   * @return  self
   */ 
  public function setLink($link)
  {
    $this->link = $link;

    return $this;
  }

  /**
   * Get the value of message
   */ 
  public function getMessage()
  {
    return $this->message;
  }

  /**
   * Set the value of message
   *
   * @return  self
   */ 
  public function setMessage($message)
  {
    $this->message = $message;

    return $this;
  }

  public function geFBOptionsArray(){
    return [
      'message' => $this->getMessage(),
      //'link'    => $this->getLink(), 
      'link' => 'https://www.analisisdigital.com.ar',
      //'name'    => $this->getName(),
      //'source'  => $this->getSource()
      'source' => 'https://www.analisisdigital.com.ar/sites/default/files/styles/noticias_front_celular/public/imagenNoticiaDigital/ve.jpg?itok=L9U016Z1&timestamp=1649343701'
    ];
  }

  public function geTWOptionsArray(){
    return [
      'message' => $this->getMessage(),
      'link'    => $this->getLink(), 
      'name'    => $this->getName(),
      'source'  => $this->getSource()
    ];
  }
}