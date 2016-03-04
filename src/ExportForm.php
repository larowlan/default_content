<?php

/**
 * @file
 * Contains \Drupal\default_content\ExportForm.
 */

namespace Drupal\default_content;

use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Converts a node to a config entity and shows it on screen
 * We use the EntityForm here because it is an easy way to get the node from the routing system
 */
class ExportForm extends \Drupal\Core\Form\FormBase {

  private $default_content_manager;
  private $entity;
  /**
   * Constructs a ContentEntityForm object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct($entity_type_manager, $route_match, $default_content_manager) {
    $params = $route_match->getParameters()->all();
    list($entity_type_id, $id) = each($params);

    $this->entity = $entity_type_manager->getStorage($entity_type_id)->load($id);
    $this->defaultContentManager = $default_content_manager;
  }

  static function create(\Symfony\Component\DependencyInjection\ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('current_route_match'),
      $container->get('default_content.manager')
    );
  }

  function getFormId() {
    return 'default_content_export_form';
  }

  function buildForm(array $form, FormStateInterface $form_state) {
    $form['output'] = [
      '#title' => $this->t('Output'),
      '#type' => 'radios',
      '#options' => [
        'download' => $this->t('Download'),
        'screen' => $this->t('Print to screen')
      ],
      '#required' => TRUE,
    ];
    $form['relations'] = [
      '#title' => $this->t('Include relations'),
      '#type' => 'checkbox',
      '#states' => [
        'visible' => [
          ':input[name=output]' => ['value' => 'screen']
        ]
      ]
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Export'),
      '#submit' => [[$this, 'submitForm']]
    ];
    if ($output = $form_state->get('exported')) {
      foreach ($output as $entity_type_id => $entities) {
        foreach ($entities as $uuid => $entity) {
          $form['exported'] = [
            '#title' => $entity_type_id,
            '#type' => 'textarea',
            '#description' => $this->t('Put this code in a file, in @path', ['@path' => 'MY_MODULE/content/'.$entity_type_id.'/'.$uuid.'.json']),
            '#value' => $entity,
            '#input' => FALSE
          ];
        }
      }

    }
    return $form;
  }

  protected function getEditedFieldNames(FormStateInterface $form_state) {
    $fieldnames = getEditedFieldNames($form_state);
    $fieldnames[] = 'output';
    $fieldnames[] = 'relations';
    return $fieldnames;
  }

  //this is being called statically, for some reason
  function submitForm(array &$form, FormStateInterface $form_state) {
    $params = \Drupal::routeMatch()->getParameters()->all();
    list($entity_type_id, $id) = each($params);


    if ($form_state->getValue('relations')) {
      $entities = $this->defaultContentManager
        ->exportContentWithReferences($entity_type_id, $id);
    }
    else {
      $entity = \Drupal::entityTypeManager()->getStorage($entity_type_id)->load($id);
      $json = $this->defaultContentManager->exportContent($entity_type_id, $id);
      $entities = [
        $entity_type_id => [
          $entity->uuid() => $json
        ]
      ];
    }


    if ($form_state->getValue('output') == 'download') {
      if (!isset($entity)) {
        drupal_set_message('creating of the zip file is not yet implemented');//@todo
      }
      $headers = [
        'Content-Type' => 'application/octet-stream',
        'Content-Length' => strlen($content),
        'Content-Disposition' =>  'attachment; filename='.$entity->uuid().'.json'
      ];

      $content = $entities[$entity_type_id][$entity->uuid()];
      $response = new Response($content, 200, $headers);
      $response->send();
      exit;
    }
    else {
      $form_state->set('exported', $entities);
      $form_state->setRebuild(TRUE);
    }
  }


}
