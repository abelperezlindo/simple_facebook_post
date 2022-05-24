<?php

namespace Drupal\analisis_autopost\Controller;

use Drupal;
use Drupal\Core\Form\FormBuilder;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Session\AccountInterface;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;



/**
 * Generic Controller class.
 */
abstract class Generic extends ControllerBase {
  /**
   * The Form builder.
   *
   * @var \Drupal\Core\Form\FormBuilder
   */
  protected $formBuilder;

  /**
   * Databse Connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $db;

  /**
   * Request.
   *
   * @var Symfony\Component\HttpFoundation\RequestStack
   */
  protected $request;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs the Controller.
   *
   * @param \Drupal\Core\Form\FormBuilder $form_builder
   *   The Form builder.
   * @param \Drupal\Core\Database\Connection $con
   *   The database connection.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request
   *   Request stack.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user. 
   */
  public function __construct(FormBuilder $form_builder, Connection $con, RequestStack $request, AccountInterface $current_user) {
    $this->formBuilder  = $form_builder;
    $this->db           = $con;
    $this->request      = $request;
    $this->currentUser  = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
    $container->get('form_builder'),
    $container->get('database'),
    $container->get('request_stack'),
    $container->get('current_user'),
    );
  }

}
