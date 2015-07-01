<?php

namespace Pages\Components\PageForm;

use App\Components\AControl;
use Kdyby\Doctrine\EntityManager;
use Nette;
use Nette\Application\UI;
use Nette\Forms\Controls\SubmitButton;
use Nette\Utils\ArrayHash;
use Pages\Page;
use Pages\PageCategory;
use Pages\PageProcess;
use Users\User;

/**
 * @method onSave(PageForm $control, Page $entity)
 * @method onPublish(PageForm $control, Page $entity)
 * @method onComplete(PageForm $control)
 * @method onException(PageForm $control, \Exception $exc)
 */
class PageForm extends AControl
{

	/** @var \Closure[] */
	public $onSave = [];

	/** @var \Closure[] */
	public $onPublish = [];

	/** @var \Closure[] */
	public $onComplete = [];

	/** @var \Closure[] */
	public $onException = [];

	/** @var PageProcess */
	private $pageProcess;

	/** @var EntityManager */
	private $em;

	/** @var Page */
	private $editablePage;

	public function __construct($editablePage, PageProcess $pageProcess, EntityManager $em)
	{
		if ($editablePage === NULL) { //NEW
			$editablePage = new Page;
		}
		$this->editablePage = $editablePage;
		$this->pageProcess = $pageProcess;
		$this->em = $em;
	}

	public function render(array $parameters = NULL)
	{
		if ($parameters) {
			$this->template->parameters = ArrayHash::from($parameters);
		}
		$this->template->showPublish = $this->editablePage->isPublished() ? FALSE : TRUE;
		$this->template->render($this->templatePath ?: __DIR__ . '/templates/PageForm.latte');
	}

	protected function createComponentPageForm()
	{
		$form = new UI\Form;
		$form->addProtection();
		$form->addText('title', 'Název:')->setRequired('Je zapotřebí vyplnit název stránky.');
		$form->addText('slug', 'URL slug:');
		$form->addTinyMCE('editor', NULL)
			->setRequired('Je zapotřebí napsat nějaký text.');

		$authors = $this->em->getRepository(User::class)->findPairs('email');
		$user_id = $this->presenter->user->id;
		$form->addMultiSelect('authors', 'Autor:',
			[NULL => 'Bez autora'] + $authors
		)->setDefaultValue(array_key_exists($user_id, $authors) ? $user_id : 0);

		$form->addMultiSelect('categories', 'Kategorie:',
			[NULL => 'Bez kategorie'] +
			$this->em->getRepository(PageCategory::class)->findPairs('name')
		);

		if ($this->editablePage !== NULL) { //EDITING
			$form->setDefaults([
				'title' => $this->editablePage->title,
				'slug' => $this->editablePage->slug,
				'editor' => $this->editablePage->body,
				'authors' => $this->editablePage->getAuthorsIds(),
				'categories' => $this->editablePage->getCategoriesIds(),
			]);
		}

		$form->addSubmit('save', 'Uložit')->onClick[] = $this->savePage;
		$form->addSubmit('publish', 'Publikovat')->onClick[] = $this->publishPage;
		return $form;
	}

	public function savePage(SubmitButton $sender)
	{
		try {
			$entity = $this->editablePage;
			$this->fillEntityWithValues($entity, $sender->getForm()->getValues());
			$this->pageProcess->onSave[] = function (PageProcess $process, Page $page) use ($entity) {
				$this->em->flush($page);
				$this->onSave($this, $entity);
			};
			$this->pageProcess->save($entity);
		} catch (\Exception $exc) {
			$this->onException($this, $exc);
		}
		$this->onComplete($this);
	}

	public function publishPage(SubmitButton $sender)
	{
		try {
			$entity = $this->editablePage;
			$this->fillEntityWithValues($entity, $sender->getForm()->getValues());
			$this->pageProcess->onPublish[] = function (PageProcess $process, Page $page) use ($entity) {
				$this->em->flush($page);
				$this->onPublish($this, $entity);
			};
			$this->pageProcess->publish($entity);
		} catch (\Exception $exc) {
			$this->onException($this, $exc);
		}
		$this->onComplete($this);
	}

	private function fillEntityWithValues(Page $entity, ArrayHash $values)
	{
		$entity->setTitle($values->title);
		$entity->setBody($values->editor);

		$entity->clearAuthors();
		if (!in_array(NULL, $values->authors)) {
			foreach ($values->authors as $authorId) {
				$authorRef = $this->em->getPartialReference(User::class, $authorId);
				$entity->addAuthor($authorRef);
			}
		}

		$entity->clearCategories();
		if (!in_array(NULL, $values->categories)) {
			foreach ($values->categories as $categoryId) {
				$categoryRef = $this->em->getPartialReference(PageCategory::class, $categoryId);
				$entity->addCategory($categoryRef);
			}
		}
	}

}

interface IPageFormFactory
{
	/**
	 * @param NULL|Page $editablePage
	 *
	 * @return PageForm
	 */
	public function create($editablePage);
}
