<?php
/**
 * Provides a post-centric controler for contents
 *
 * JSC (http://jasonsnider.com/jsc)
 * Copyright 2013, Jason D Snider. (http://jasonsnider.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2013, Jason D Snider. (http://jasonsnider.com)
 * @link http://jasonsnider.com
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @author Jason D Snider <jason@jasonsnider.com>
 * @package       Users
 */
App::uses('ContentsAppController', 'Contents.Controller');

/**
 * Provides a post-centric controler for contents
 * @author Jason D Snider <jason@jasonsnider.com>
 * @package Posts
 */
class PostsController extends ContentsAppController {

    /**
     * Holds the name of the controller
     *
     * @var string
     */
    public $name = 'Posts';

    /**
     * Call the components to be used by this controller
     *
     * @var array
     */
    //public $components = array();

    /**
     * Called before action
     * @return void
     */
    public function beforeFilter() {
        parent::beforeFilter();
        $this->Auth->allow(
            'index',
            'view'
        );
        $this->Authorize->allow();
    }
    
    /**
     * The models used by the controller
     *
     * @var array
     */
    public $uses = array(
		'Contents.Content',
        'Contents.Post'
    );

    /**
     * Displays an index of all content
     * @return void
     */
    public function index() {

        $this->paginate = array(
            'conditions' => array(
                'Post.content_type'=>'post',
                'Post.content_status'=>'published',
            ),
            'contain'=>array(
                'CreatedUser'=>array(
                    'UserProfile'=>array()
                ),
            ),
            'order'=>'Post.created DESC',
            'limit' => 10
        );

        $this->request->checkForMeta = true;
        $data = $this->paginate('Post');
        $this->set(compact('data'));
    }
    
    /**
     * Displays content; a single page or post, etc.
     * @param string $token
     * @return void
     */
    public function view($token) {
        
        $content = $this->Post->fetch($token);
        
        if(empty($content)){
            throw new NotFoundException();
        }

        //Send the id back to the view
        $id = $content['Post']['id'];
        
        $this->request->title = $content['Post']['title'];
        
		$relatedContent = $this->Content->listContentsByCategory(
			$content['Post']['category_id'],
			Configure::read('JSC.Posts.Related.limit'),
			Configure::read('JSC.Posts.Related.model')
		);
		
        $this->set(compact(
            'content',
			'relatedContent',
            'id'
        ));
    }
    
    /**
     * A method for creating a new content
     * @return void
     */
    public function admin_create() {
        if(!empty($this->request->data)){
			$this->request->data['Post']['slug'] = $this->Post->slug($this->request->data);
			
            if($this->Post->save($this->request->data)){
                $this->Session->setFlash(__('Post saved.'), 'success');
                $this->redirect("/admin/contents/posts/edit/{$this->Post->id}");
            }else{
                $this->Session->setFlash(__('Please correct the errors below.'), 'error');
            }
        }
		
        $this->set(compact(
            'title_for_layout'
        ));
		
        $this->request->hasEditor = true;
        $this->request->title = 'Create a Post';
    }
	
    /**
     * Allows a content to be updated
     * @param string $token
     * @return void
     */
    public function admin_edit($token) {
		
        $post = $this->Post->fetch($token);

        if(!empty($this->request->data)){
            if($this->Post->save($this->request->data['Post'])){
                $this->Session->setFlash(__('Update saved!'), 'success');
            }else{
                $this->Session->setFlash(__('Please correct the errors below!'), 'error');
            }
        }else{
            $this->request->data = $post;
        }
        
		$categories = $this->Post->Category->find('list');
        $this->set(compact(
            'categories',
            'title_for_layout'
        ));
		
        $this->request->hasEditor = true;
        $this->request->title = $post['Post']['title'];
    }
}