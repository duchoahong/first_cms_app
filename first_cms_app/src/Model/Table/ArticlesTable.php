<?php
// src/Model/Table/ArticlesTable.php
namespace App\Model\Table;

use Cake\ORM\Query;

// the Validator class
use Cake\Validation\Validator;

use Cake\ORM\Table;
// the Text class
use Cake\Utility\Text;

class ArticlesTable extends Table
{
	public function initialize(array $config) 
	{
		$this->addBehavior('Timestamp');
		$this->belongsToMany('Tags', [
			'joinTable' => 'articles_tags', 
			'dependent' => true
		]);// Add this line
	}

	public function beforeSave($event, $entity, $options)
	{
		if($entity->isNew() && !$entity->slug) 
		{
			$sluggedTitle = Text::slug($entity->title);
			// trim slug to maximum length defined in schema
			$entity->slug = substr($sluggedTitle, 0, 191);
		}
	}

	public function validationDefault(Validator $validator)
	{
		$validator->allowEmptyString('title',false)
					->minLength('title', 10)
					->maxLength('title', 255)
					->allowEmptyString('body',false)
					->minLength('body', 10);
		return$validator;
	}

	// The $query argument is a query builder instance.
	// The $options array will contain the 'tags' option we passed
	// to find('tagged') in our controller action.
	public function findTagged(Query $query,array$options)
	{
		$columns = [
				'Articles.id', 
				'Articles.user_id', 
				'Articles.title',
				'Articles.body', 
				'Articles.published', 
				'Articles.created',
				'Articles.slug',
			];
		$query = $query->select($columns)->distinct($columns);
		if(empty($options['tags'])) 
		{
			// If there are no tags provided, find articles that have no tags.
			$query->leftJoinWith('Tags')->where(['Tags.title IS' =>null]);
		}
		else
		{
			// Find articles that have one or more of the provided tags.
			$query->innerJoinWith('Tags')->where(['Tags.title IN' => $options['tags']]);
		}
		return $query->group(['Articles.id']);
	}

}