<?php

namespace Drupal\scheduler_content_moderation_integration\Plugin\Field\FieldWidget;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsSelectWidget;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'scheduler moderation' widget.
 *
 * @FieldWidget(
 *   id = "scheduler_moderation",
 *   label = @Translation("Scheduler Moderation"),
 *   description = @Translation("Select list for choosing a state. Defined by Scheduler Content Moderation Integration module."),
 *   field_types = {
 *     "list_string",
 *   }
 * )
 */
class SchedulerModerationWidget extends OptionsSelectWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $value = isset($items[$delta]->value)
      ? $items[$delta]->value
      : NULL;

    $element['#element_validate'][] = [$this, 'validateElement'];
    $element['#default_value'] = $value;

    unset($element['#options']['_none']);

    return $element;
  }

  /**
   * Element validation handler for the scheduler moderation widget.
   *
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @todo Figure out how to make this code a bit more graceful and avoiding
   * checking array keys directly.
   */
  public static function validateElement(array $element, FormStateInterface $form_state) {
    $name = $element['#id'];

    if ($name === 'edit-publish-state-0') {
      $publish_on = $form_state->getValue('publish_on');
      if (isset($publish_on[0]['value']) && empty($element['#value'])) {
        $form_state->setError($element, t('Choose the published state for the node.'));
      }
    }
    else {
      $unpublish_on = $form_state->getValue('unpublish_on');
      if (isset($unpublish_on[0]['value']) && empty($element['#value'])) {
        $form_state->setError($element, t('Choose the unpublished state for the node.'));
      }
    }

    $form_state->setValueForElement($element, [
      $element['#key_column'] => $element['#value'],
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    if ($field_definition instanceof BaseFieldDefinition && $field_definition->getProvider() === 'scheduler_content_moderation_integration') {
      return TRUE;
    }
  }

}
