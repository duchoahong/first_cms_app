<?php
// src/Controller/ArticlesController.php
namespace App\Controller;

class ArticlesController extends AppController
{
	public function initialize()
	{
		parent::initialize();
		$this->loadComponent('Paginator');
		$this->loadComponent('Flash');// Include the FlashComponent
		$this->Auth->allow(['tags']);
	}

	public function index()
	{
		$this->loadComponent('Paginator');
		$articles = $this->Paginator->paginate($this->Articles->find());
		$this->set(compact('articles'));
	}

	// Add to existing src/Controller/ArticlesController.php file
	public function view($slug = null)
	{
		$article = $this->Articles->findBySlug($slug)->contain(['Tags'])->firstOrFail();
		print_r($article->tag_string );
		exit;
		$this->set(compact('article'));
	}

	public function add()
	{
		$article = $this->Articles->newEntity();
		if($this->request->is('post')) 
		{
			$article = $this->Articles->patchEntity($article, $this->request->getData());
			// Hardcoding the user_id is temporary, and will be removed later
			// when we build authentication out.
			$article->user_id = $this->Auth->user('id');
			if($this->Articles->save($article)) 
				{
					$this->Flash->success(__('Your article has been saved.'));
					return$this->redirect(['action' => 'index']);
				}
			$this->Flash->error(__('Unable to add your article.'));
		}
		// Get a list of tags
		$tags = $this->Articles->Tags->find('list');
		// Set tags to the view context
		$this->set('tags', $tags);

		$this->set('article', $article);

		// Other actions
	}	

	public function edit($slug)
	{
		$article = $this->Articles->findBySlug($slug)->contain(['Tags'])->firstOrFail();

		if($this->request->is(['post', 'put'])) 
		{
			$this->Articles->patchEntity($article, $this->request->getData());
			if($this->Articles->save($article)) 
			{
				$this->Flash->success(__('Your article has been updated.'));
				return$this->redirect(['action' => 'index']);
			}
			$this->Flash->error(__('Unable to update your article.'));
		}
		// Get a list of tags
		$tags = $this->Articles->Tags->find('list');

		// Set selected article_tag
		// $article_tag = $this->Articles->Tags->find('id');
		// print_r($article_tag);
		// exit;

		// Set tags to the view context
		$this->set('tags', $tags);

		$this->set('article', $article);
	}

	public function delete($slug)
	{
		$this->request->allowMethod(['post', 'delete']);
		$article = $this->Articles->findBySlug($slug)->firstOrFail();
		if($this->Articles->delete($article)) 
		{
			$this->Flash->success(__('The {0} article has been deleted.', $article->˓→title));
			return$this->redirect(['action' => 'index']);
		}
	}

	public function tags(...$tags)
	{
		// The 'pass' key is provided by CakePHP and contains all
		// the passed URL path segments in the request.
			// $tags = $this->request->getParam('pass');

		// Use the ArticlesTable to find tagged articles.
		$articles = $this->Articles->find('tagged', ['tags' => $tags]);
		// Pass variables into the view template context.
		$this->set(['articles' => $articles,'tags' => $tags]);
	}

	public function beforeSave($event, $entity, $options)
	{
		if($entity->tag_string) 
		{
			$entity->tags = $this->_buildTags($entity->tag_string);
		}// Other code
	}

	protected function _buildTags($tagString)
	{
		// Trim tags
		$newTags = array_map('trim', explode(',', $tagString));
		// Remove all empty tags
		$newTags = array_filter($newTags);
		// Reduce duplicated tags
		$newTags = array_unique($newTags);
		$out = [];$query = $this->Tags->find()->where(['Tags.title IN' => $newTags]);
		// Remove existing tags from the list of new tags.
		foreach($query->extract('title') as $existing) 
		{	
			$index = array_search($existing, $newTags);
			if($index !==false) 
			{
				unset($newTags[$index]);
			}
		}
		// Add existing tags.
		foreach($query as $tag) 
		{
			$out[] = $tag;
		}
		// Add new tags.
		foreach($newTags as $tag) 
		{
			$out[] = $this->Tags->newEntity(['title' => $tag]);
		}
		return $out;
	}

	public function isAuthorized($user)
	{
		$action = $this->request->getParam('action');
		// The add and tags actions always allowed to logged in users.
		if (in_array($action, ['add', 'tags']))
		{
			return true;
		}

		// All other actions require a slug.
		$slug = $this->request->getParam('pass.0');
		if (!$slug)
		{
			return false;
		}

		// Check that the article belongs to the current user.
		$article = $this->Articles->findBySlug($slug)->first();

		return $article->user_id === $user['id'];
	}
}