<?php

namespace Drupal\Tests\book\Functional;

use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\Role;
use Drupal\Tests\BrowserTestBase;

/**
 * Create a book, add pages, and test book translations.
 *
 * @group book
 */
class BookTranslationTest extends BrowserTestBase {
  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = [
    'book',
    'block',
    'node_access_test',
    'book_test',
    'locale',
    'content_translation',
    'language',
  ];

  /**
   * A book node.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $bookcover;

  /**
   * A book node.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $bookpage1;


  /**
   * A user with permission to create and edit books.
   *
   * @var object
   */
  protected $bookAuthor;

  /**
   * A user with permission to create and edit books and to administer blocks.
   *
   * @var object
   */
  protected $adminUser;

  /**
   * A user with permission to view a book and access printer-friendly version.
   *
   * @var object
   */
  protected $webUser;

  /**
   * The other language available for content.
   *
   * @var string
   */
  protected $secondLanguage = 'de';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // node_access_test requires a node_access_rebuild().
    node_access_rebuild();

    // Create users.
    $this->bookAuthor = $this->drupalCreateUser([
      'create new books',
      'create book content',
      'edit own book content',
      'add content to books',
      'create content translations',
    ]);
    $this->adminUser = $this->drupalCreateUser([
      'create new books',
      'create book content',
      'edit any book content',
      'delete any book content',
      'add content to books',
      'administer blocks',
      'administer permissions',
      'administer book outlines',
      'node test view',
      'administer content types',
      'administer site configuration',
      'create content translations',
    ]);
    $this->webUser = $this->drupalCreateUser([
      'access printer-friendly version',
      'node test view',
      'access content',
    ]);
  }

  /**
   * Test that the link text to the next page is translated.
   */
  public function testBookNextLink() {
    $localized_title = 'DE Buch Seite 1';
    // Preparation.
    $this->enableSecondLanguage();

    $nodes = $this->createBook();

    $this->translateBookPage($this->bookcover, "DE Buch", "Buchdeckel auf Deutsch");
    $this->translateBookPage($this->bookpage1, $localized_title, "Seite 1 auf Deutsch");

    $this->drupalLogin($this->webUser);

    // Go to book page.
    $this->drupalGet($this->secondLanguage . '/node/' . $this->bookcover->id());

    /* Identify hyperlink for assertions */
    $elements = $this->xpath('//a[@rel="next"]');
    $this->assertThat(count($elements), $this->equalTo(1), "No element found.");
    $element = $elements[0];
    $language = \Drupal::languageManager()->getLanguage($this->secondLanguage);
    $elementUrl = Url::fromUri('internal:/' . $this->secondLanguage . '/node/' . $this->bookpage1->id(), ['language' => $language])->toString();
    $this->assertEquals($elementUrl, $element->getAttribute('href'), 'Anchor points to correct node');
    $this->assertContains($localized_title, $element->getText(), 'Text is not localized');
  }

  /**
   * Test that the link text to the previous page is translated.
   */
  public function testBookPrevLink() {
    $localized_title = 'DE Buch Deckel';
    // Preparation.
    $this->enableSecondLanguage();

    $nodes = $this->createBook();

    $this->translateBookPage($this->bookcover, $localized_title, "Buchdeckel auf Deutsch");
    $this->translateBookPage($this->bookpage1, "DE Seite 1", "Seite 1 auf Deutsch");

    $this->drupalLogin($this->webUser);

    // Go to book page.
    $this->drupalGet($this->secondLanguage . '/node/' . $this->bookpage1->id());

    /* Identify hyperlink for assertions */
    $elements = $this->xpath('//a[@rel="prev"]');
    $this->assertThat(count($elements), $this->equalTo(1), "No element found.");
    $element = $elements[0];
    $language = \Drupal::languageManager()->getLanguage($this->secondLanguage);
    $elementUrl = Url::fromUri('internal:/' . $this->secondLanguage . '/node/' . $this->bookcover->id(), ['language' => $language])->toString();
    $this->assertEquals($elementUrl, $element->getAttribute('href'), 'Anchor points to correct node');
    $this->assertContains($localized_title, $element->getText(), 'Text is not localized');
  }

  /**
   * Test that the breadcrumb title is translated.
   */
  public function testBookBreadcrumb() {
    $localized_title = 'DE Buch Deckel';
    // Preparation.
    $this->enableSecondLanguage();

    $nodes = $this->createBook();

    $this->translateBookPage($this->bookcover, $localized_title, "Buchdeckel auf Deutsch");
    $this->translateBookPage($this->bookpage1, "DE Seite 1", "Seite 1 auf Deutsch");

    $this->drupalPlaceBlock('system_breadcrumb_block');

    $this->drupalLogin($this->webUser);

    // Go to book page.
    $this->drupalGet($this->secondLanguage . '/node/' . $this->bookpage1->id());

    /* Identify hyperlink for assertions. Second hyperlink is pointing to parent page */
    $elements = $this->xpath('(//nav[@class="breadcrumb"]//a)[2]');
    $this->assertThat(count($elements), $this->equalTo(1), "No element found.");
    $element = $elements[0];
    $language = \Drupal::languageManager()->getLanguage($this->secondLanguage);
    $elementUrl = Url::fromUri('internal:/' . $this->secondLanguage . '/node/' . $this->bookcover->id(), ['language' => $language])->toString();
    $this->assertEquals($elementUrl, $element->getAttribute('href'), 'Anchor points to correct node');
    $this->assertContains($localized_title, $element->getText(), 'Text is not localized');
  }

  /**
   * Test that the title in the table of contents is translated.
   */
  public function testBookTableOfContents() {
    $localized_title = 'DE Buch Seite 1';
    // Preparation.
    $this->enableSecondLanguage();

    $nodes = $this->createBook();

    $this->translateBookPage($this->bookcover, "DE Buch", "Buchdeckel auf Deutsch");
    $this->translateBookPage($this->bookpage1, $localized_title, "Seite 1 auf Deutsch");

    $this->drupalLogin($this->webUser);

    // Go to book page.
    $this->drupalGet($this->secondLanguage . '/node/' . $this->bookcover->id());

    /* Identify hyperlink for assertions: first anchor in book-navigation */
    $elements = $this->xpath('(//nav[@id="book-navigation-' . $this->bookcover->id() . '"]/ul[@class="menu"]/li/a)[1]');
    $this->assertThat(count($elements), $this->equalTo(1), "No element found.");
    $element = $elements[0];
    $language = \Drupal::languageManager()->getLanguage($this->secondLanguage);
    $elementUrl = Url::fromUri('internal:/' . $this->secondLanguage . '/node/' . $this->bookpage1->id(), ['language' => $language])->toString();
    $this->assertEquals($elementUrl, $element->getAttribute('href'), 'Anchor points to correct node');
    $this->assertContains($localized_title, $element->getText(), 'Text is not localized');
  }

  /**
   * Install content_translation module a a second.
   */
  public function enableSecondLanguage() {
    $this->drupalLogin($this->rootUser);
    $this->addSecondLanguage();
    $this->enableContentTypeTranslation();
    // Rebuild the container so that the new languages are picked up by services
    // that hold a list of languages.
    $this->rebuildContainer();
  }

  /**
   * Enable translation for book content type.
   */
  protected function enableContentTypeTranslation() {
    \Drupal::service('content_translation.manager')->setEnabled('node', 'book', TRUE);
    $roles = $this->adminUser->getRoles(TRUE);
    Role::load(reset($roles))
      ->grantPermission('create content translations')
      ->grantPermission('translate any entity')
      ->save();
    $roles = $this->bookAuthor->getRoles(TRUE);
    Role::load(reset($roles))
      ->grantPermission('create content translations')
      ->grantPermission('translate any entity')
      ->save();
    drupal_static_reset();
    \Drupal::entityManager()->clearCachedDefinitions();
    \Drupal::service('router.builder')->rebuild();
    \Drupal::service('entity.definition_update_manager')->applyUpdates();

    \Drupal::service('content_translation.manager')->setEnabled('node', 'book', TRUE);
    drupal_static_reset();
    \Drupal::entityManager()->clearCachedDefinitions();
    \Drupal::service('router.builder')->rebuild();
    \Drupal::service('entity.definition_update_manager')->applyUpdates();
  }

  /**
   * Add a second content language.
   */
  protected function addSecondLanguage() {
    $langcode = $this->secondLanguage;

    // Check to make sure that language has not already been installed.
    $this->drupalGet('admin/config/regional/language');

    if (strpos($this->getTextContent(), 'languages[' . $langcode . ']') === FALSE) {
      // Doesn't have language installed so add it.
      $edit = [];
      $edit['predefined_langcode'] = $langcode;
      $this->drupalPostForm('admin/config/regional/language/add', $edit, t('Add language'));

      // Reset language list.
      \Drupal::languageManager()->reset();
      $languages = \Drupal::languageManager()->getLanguages();
      $this->assertTrue(array_key_exists($langcode, $languages), 'Language was installed successfully.');

      if (array_key_exists($langcode, $languages)) {
        $this->assertRaw(t('The language %language has been created and can now be used.', ['%language' => $languages[$langcode]->getName()]), 'Language has been created.');
      }
    }
    else {
      // It's installed. No need to do anything.
      $this->assertTrue(TRUE, 'Language [' . $langcode . '] already installed.');
    }
  }

  /**
   * Install content_translation module.
   */
  protected function installContentTranslationModule() {
    $edit = [
      'modules[content_translation][enable]' => TRUE,
      'modules[language][enable]' => TRUE,
    ];
    $this->drupalPostForm('admin/modules', $edit, t('Install'));

    $this->assertText(t('This site has only a single language enabled. Add at least one more language in order to translate content.'));
  }

  /**
   * Translate a book page.
   *
   * @param \Drupal\node\Entity\Node $node
   *   Book page.
   * @param string $title
   *   The translated title.
   * @param string $body
   *   The translated body.
   */
  public function translateBookPage(Node $node, $title, $body) {
    $this->drupalLogin($this->bookAuthor);

    $langcode = $this->secondLanguage;
    $this->drupalGet('node/' . $node->id() . '/translations/add/en/de');

    $edit = [];
    $edit["title[0][value]"] = $title;
    $edit["body[0][value]"] = $body;
    $this->drupalPostForm(NULL, $edit, t('Save (this translation)'));
    $options = [
      'attributes' => ['hreflang' => $langcode],
    ];
    $link = Link::fromTextAndUrl($title, Url::fromUri('internal:/' . $langcode . '/node/' . $node->id(), $options))->toString();
    $this->drupalLogout();
  }

  /**
   * Creates a new book with a page hierarchy.
   *
   * @return \Drupal\node\NodeInterface[]
   *   An array of book pages.
   */
  public function createBook() {
    // Create new book.
    $this->drupalLogin($this->bookAuthor);

    $this->bookcover = $this->createBookNode('EN Book', 'Book cover in English', 'new');
    $book = $this->bookcover;

    /*
     * Add page hierarchy to book.
     * Book
     *  |- Node 0
     *   |- Node 1
     *   |- Node 2
     *  |- Node 3
     *  |- Node 4
     */
    $nodes = [];
    // Node 0.
    $nodes[] = $this->bookpage1 = $this->createBookNode('EN Page 1', 'Book page in English', $book->id());
    // Node 1.
    $nodes[] = $this->createBookNode('EN Page 2', 'Book page in English', $book->id(), $nodes[0]->book['nid']);
    // Node 2.
    $nodes[] = $this->createBookNode('EN Page 3', 'Book page in English', $book->id(), $nodes[0]->book['nid']);
    // Node 3.
    $nodes[] = $this->createBookNode('EN Page 4', 'Book page in English', $book->id());
    // Node 4.
    $nodes[] = $this->createBookNode('EN Page 5', 'Book page in English', $book->id());

    $this->drupalLogout();

    return $nodes;
  }

  /**
   * Creates a book node.
   *
   * @param string $title
   *   The page title.
   * @param string $body
   *   The page body text.
   * @param int|string $book_nid
   *   A book node ID or set to 'new' to create a new book.
   * @param int|null $parent
   *   (optional) Parent book reference ID. Defaults to NULL.
   *
   * @return \Drupal\node\NodeInterface
   *   The created node.
   */
  public function createBookNode($title, $body, $book_nid, $parent = NULL) {
    // $number does not use drupal_static as it should not be reset
    // since it uniquely identifies each call to createBookNode().
    // Used to ensure that when sorted nodes stay in same order.
    static $number = 0;

    $edit = [];
    $edit['title[0][value]'] = $title;
    $edit['body[0][value]'] = $body;
    $edit['book[bid]'] = $book_nid;

    if ($parent !== NULL) {
      $this->drupalPostForm('node/add/book', $edit, t('Change book (update list of parents)'));

      $edit['book[pid]'] = $parent;
      $this->drupalPostForm(NULL, $edit, t('Save'));
      // Make sure the parent was flagged as having children.
      $parent_node = \Drupal::entityManager()->getStorage('node')->loadUnchanged($parent);
      $this->assertFalse(empty($parent_node->book['has_children']), 'Parent node is marked as having children');
    }
    else {
      $this->drupalPostForm('node/add/book', $edit, t('Save'));
    }

    // Check to make sure the book node was created.
    $node = $this->drupalGetNodeByTitle($edit['title[0][value]']);
    $this->assertNotNull(($node === FALSE ? NULL : $node), 'Book node found in database.');
    $number++;

    return $node;
  }

}
