<?php namespace App\Admin;

use App\Entity\Book;
use App\Entity\BookRevision;
use App\Entity\TextRepository;
use App\Util\Language;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Sonata\AdminBundle\Route\RouteCollection;
use Symfony\Component\Form\FormEvents;

class BookAdmin extends Admin {
	protected $baseRoutePattern = 'book';
	protected $baseRouteName = 'admin_book';

	public $extraActions = 'App:BookAdmin:extra_actions.html.twig';

	private $textRepository;

	public function setTextRepository(TextRepository $r) {
		$this->textRepository = $r;
	}

	protected function configureRoutes(RouteCollection $collection) {
		$collection->remove('create');
	}

	protected function configureShowField(ShowMapper $showMapper) {
		$showMapper
			->add('slug')
			->add('title')
			->add('authors')
			->add('subtitle')
			->add('title_extra')
			->add('orig_title')
			->add('lang')
			->add('orig_lang')
			->add('year')
			//->add('trans_year')
			->add('type')
			->add('sequence')
			->add('seqnr')
			->add('category')
			->add('removed_notice')
			->add('texts')
			->add('links', null, array('label' => 'Site Links'))
			->add('created_at')
		;
	}

	protected function configureListFields(ListMapper $listMapper) {
		$listMapper
			->add('url', 'string', array('template' => 'App:BookAdmin:list_url.html.twig'))
			->add('slug')
			->addIdentifier('title')
			->add('id')
			->add('type')
			->add('sfbg', 'string', array('template' => 'App:BookAdmin:list_sfbg.html.twig'))
			->add('puk', 'string', array('template' => 'App:BookAdmin:list_puk.html.twig'))
			->add('_action', 'actions', array(
				'actions' => array(
					'view' => array(),
					'edit' => array(),
					'delete' => array(),
				)
			))
		;
	}

	//public $preFormContent = 'App:BookAdmin:form_datafiles.html.twig';

	protected function configureFormFields(FormMapper $formMapper) {
			//->add('sfbg', 'string', array('template' => 'App:BookAdmin:form_sfbg.html.twig'))
			//->add('datafiles', 'string', array('template' => 'App:BookAdmin:form_datafiles.html.twig'))
		$formMapper->with('General attributes');
		$formMapper
			->add('slug')
			->add('title')
			->add('lang', 'choice', array('choices' => Language::getLangs()))
			->add('orig_lang', 'choice', array('required' => false, 'choices' => Language::getLangs()))
			->add('type', 'choice', array('choices' => Book::getTypeList()))
			->add('bookAuthors', 'sonata_type_collection', array(
				'by_reference' => false,
				'required' => false,
			), array(
				'edit' => 'inline',
				'inline' => 'table',
			));
		$formMapper->with('Extra attributes');
		$formMapper
			->add('subtitle', null, array('required' => false))
			->add('title_extra', null, array('required' => false))
			->add('orig_title', null, array('required' => false))
			->add('year')
			//->add('trans_year', null, array('required' => false))
			->add('sequence', null, array('required' => false, 'query_builder' => function ($repo) {
				return $repo->createQueryBuilder('e')->orderBy('e.name');
			}))
			->add('seqnr', null, array('required' => false))
			->add('category', null, array('required' => false, 'query_builder' => function ($repo) {
				return $repo->createQueryBuilder('e')->orderBy('e.name');
			}))
			->add('links', 'sonata_type_collection', array(
				'by_reference' => false,
				'required' => false,
				'label' => 'Site Links',
			), array(
				'edit' => 'inline',
				'inline' => 'table',
				'sortable' => 'site_id'
			));
		$formMapper->with('Textual content');
		$formMapper
			->add('raw_template', 'textarea', array(
				'label' => 'Template',
				'required' => false,
				'trim' => false,
				'attr' => array(
					'class' => 'span12',
				),
			))
			->add('annotation', 'textarea', array(
				'required' => false,
				'trim' => false,
				'attr' => array(
					'class' => 'span12',
				),
			))
			->add('extra_info', 'textarea', array(
				'required' => false,
				'trim' => false,
				'attr' => array(
					'class' => 'span12',
				),
			))
			->add('revision_comment', 'text', array('required' => false))
			->add('removed_notice');
		$formMapper->getFormBuilder()->addEventListener(FormEvents::PRE_SUBMIT, array($this, 'fixNewLines'));
	}

	protected function configureDatagridFilters(DatagridMapper $datagrid) {
		$datagrid
			->add('title')
			->add('subtitle')
			->add('type')
			->add('has_cover')
			->add('has_anno')
		;
	}

	public function preUpdate($book) {
		foreach ($book->getLinks() as $link) {
			$link->setBook($book);
		}
		foreach ($book->getBookAuthors() as $bookAuthor) {
			if ($bookAuthor->getPerson()) {
				$bookAuthor->setBook($book);
			}
		}
		if ($book->textsNeedUpdate()) {
			$texts = $this->textRepository->findByIds($book->getTextIdsFromTemplate());
			$book->setTexts($texts);
		}
		if ($book->getRevisionComment()) {
			$revision = new BookRevision;
			$revision->setComment($book->getRevisionComment());
			$revision->setBook($book);
			$revision->setDate(new \DateTime);
			$revision->setFirst(false);
			$book->addRevision($revision);
		}
	}

}