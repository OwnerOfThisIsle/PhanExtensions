<?php declare(strict_types=1);

namespace Drenso\PhanExtensions\Visitor\Annotation\Base;

use Phan\PluginV2\PluginAwarePostAnalysisVisitor;
use ast\Node;

use const ast\flags\USE_NORMAL;

/**
 * When __invoke on this class is called with a node, a method
 * will be dispatched based on the `kind` of the given node.
 *
 * Visitors such as this are useful for defining lots of different
 * checks on a node based on its kind.
 *
 * @author BobV
 */
abstract class AnnotationVisitor extends PluginAwarePostAnalysisVisitor
{
  /**
   * Holds the exceptions for a specific framework
   *
   * @var array
   */
  protected $exceptions = [];

  /**
   * Visit class
   *
   * @param Node $node
   *
   * @throws \AssertionError
   */
  public function visitClass(Node $node)
  {
    $this->checkDocComment($node);
  }

  /**
   * Visit method
   *
   * @param Node $node
   *
   * @throws \AssertionError
   */
  public function visitMethod(Node $node)
  {
    $this->checkDocComment($node);
  }

  /**
   * Visit property
   *
   * @param Node $node
   *
   * @throws \AssertionError
   */
  public function visitPropElem(Node $node)
  {
    $this->checkDocComment($node);
  }

  /**
   * Retrieves the docblock for the node, and checks for the given annotations
   *
   * @param Node $node
   *
   * @throws \AssertionError
   */
  private function checkDocComment(Node $node){
    // Retrieve the doc block
    $docComment = $node->children['docComment'];

    // Ignore empty doc blocks
    if ($docComment === NULL || strlen($docComment) == 0) {
      return;
    }

    // Retrieve all annotations from the doc comment
    preg_match_all('/\s*\*\s*\@([A-Z][a-zA-Z]+)[\\\(]?/', $docComment, $matches);
    foreach ($matches[1] as $annotation){
      // Check for exceptions
      if (in_array($annotation, $this->exceptions)) continue;

      // Check for annotation
      $this->checkAnnotation($annotation);
    }
  }

  /**
   * Checks whether the given annotation is imported, and then resolves it in the namespace map.
   *
   * @param string $annotation
   *
   * @throws \AssertionError
   */
  private function checkAnnotation(string $annotation)
  {
    try {
      // Check for map to avoid exceptions
      if ($this->context->hasNamespaceMapFor(USE_NORMAL, $annotation)) {
        // Add usage of this annotation to the namespace map
        // See https://github.com/phan/phan/pull/1467
        $this->context->getNamespaceMapFor(USE_NORMAL, $annotation);
      } else {
        // The annotation is used, but not imported correctly (probably)
        $this->emit(
            'AnnotationNotImported',
            'The annotation {CLASS} was never imported (generated by DrensoAnnotation plugin)',
            [$annotation]
        );
      }
    } catch (\Exception $e) {
      // Do nothing, simply ignore
    }
  }
}
