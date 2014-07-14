<?php namespace Lavary\Menu;

use Illuminate\Support\Collection as Collection;

class Builder {
	
	/**
	 * The items container
	 *
	 * @var array
	 */
	protected $items;

	/**
	 * The Menu name
	 *
	 * @var string
	 */
	protected $name;

	/**
	 * The route group attribute stack.
	 *
	 * @var array
	 */
	protected $groupStack = array();
	
	/**
	* The reserved attributes.
	*
	* @var array
	*/
	protected $reserved = array('route', 'action', 'url', 'prefix', 'parent', 'secure', 'plaintext');

	/**
	* The last inserted item's id
	*
	* @var int
	*/
	protected   $last_id;
	
	/**
	* HTML generator
	*
	* @var Illuminate\Html\HtmlBuilder
	*/
	protected $html;
	
	/**
	* The URL generator
	*
	* @var Illuminate\Routing\UrlGenerator
	*/
	protected $url;	
	
	/**
	* The Environment instance
	*
	* @var Illuminate\View\Factory
	*/
	protected $environment;
	
	/**
	 * Initializing the menu manager
	 *
	 * @param  \Illuminate\Html\HtmlBuilder      $html
	 * @param  \Illuminate\Routing\UrlGenerator  $url
	 * @param  \Illuminate\View\Factory          $environment
	 * @return void
	 */
	public function __construct($html, $url, $environment)
	{
		$this->items       = new Collection;
		
		$this->url         = $url;
		$this->html        = $html;
		$this->environment = $environment;
	}

	/**
	 * Adds an item to the menu
	 *
	 * @param  string  $title
	 * @param  string|array  $acion
	 * @return Lavary\Menu\Item $item
	 */
	public function add($title, $options = null)
	{
	
		$item = new Item($this, $this->id(), $title, $options);
                      
		$this->items->push($item);

		// stroing the last inserted item's id
		$this->last_id = $item->id;
		
		return $item;
	}

	/**
	 * Generate an integer identifier for each new item
	 *
	 * @return int
	 */
	protected function id()
	{
		return $this->last_id + 1;
	}

	/**
	 * Add a plain text item
	 *
	 * @return Lavary\Menu\Item
	 */
	public function text($title, array $options = array())
	{
		$options['plaintext'] = true;
		
		return $this->add($title, $options);
	}

	/**
	 * Returns menu item by name
	 *
	 * @return Lavary\Menu\Item
	 */
	public function get($title){
		
		return $this->whereNickname($title)
		
					->first();		
	}

	/**
	 * Returns menu item by Id
	 *
	 * @return Lavary\Menu\Item
	 */
	public function find($id){
		
		return $this->whereId($id)
		
					->first();		
	}

	/**
	 * Returns menu item by name
	 *
	 * @return Lavary\Menu\Item
	 */
	public function item($title){
		
		return $this->whereNickname($attribute)
		
					->first();		
	}

	/**
	 * Insert a separator after the item
	 *
	 * @param  array $attributes
	 * @return void
	 */
	public function divider(array $attributes = array()){
		
		$attributes['class'] = self::formatGroupClass(array('class' => 'divider'), $attributes);
		
		$this->items->last()->divider = $attributes;
		
	}

	/**
	 * Create a menu group with shared attributes.
	 *
	 * @param  array  $attributes
	 * @param  callable  $closure
	 * @return void
	 */
	public function group($attributes, $closure)
	{
		$this->updateGroupStack($attributes);

		// Once we have updated the group stack, we will execute the user Closure and
		// merge in the groups attributes when the item is created. After we have
		// run the callback, we will pop the attributes off of this group stack.
		call_user_func($closure, $this);

		array_pop($this->groupStack);
	}

	/**
	 * Update the group stack with the given attributes.
	 *
	 * @param  array  $attributes
	 * @return void
	 */
	protected function updateGroupStack(array $attributes = array())
	{

		if (count($this->groupStack) > 0)
		{
			$attributes = $this->mergeWithLastGroup($attributes);
		}

		$this->groupStack[] = $attributes;

	}

	/**
	 * Merge the given array with the last group stack.
	 *
	 * @param  array  $new
	 * @return array
	 */
	protected function mergeWithLastGroup($new)
	{
		return self::mergeGroup($new, last($this->groupStack));
	}

	/**
	 * Merge the given group attributes.	
	 *
	 * @param  array  $new
	 * @param  array  $old
	 * @return array
	 */
	protected static function mergeGroup($new, $old)
	{
		$new['prefix'] = self::formatGroupPrefix($new, $old);
		
		$new['class']  = self::formatGroupClass($new, $old);
		
		return array_merge_recursive(array_except($old, array('prefix', 'class')), $new);
	}

	/**
	 * Format the prefix for the new group attributes.
	 *
	 * @param  array  $new
	 * @param  array  $old
	 * @return string
	 */
	public static function formatGroupPrefix($new, $old)
	{
		if (isset($new['prefix']))
		{
			return trim(array_get($old, 'prefix'), '/') . '/' . trim($new['prefix'], '/');
		}
		return array_get($old, 'prefix');
	}

	/**
	 * Get the prefix from the last group on the stack.
	 *
	 * @return string
	 */
	public function getLastGroupPrefix()
	{
		if (count($this->groupStack) > 0)
		{

			return array_get(last($this->groupStack), 'prefix', '');
		}

		return null;
	}

	/**
	 * Prefix the given URI with the last prefix.
	 *
	 * @param  string  $uri
	 * @return string
	 */
	protected function prefix($uri)
	{
		return trim(trim($this->getLastGroupPrefix(), '/').'/'.trim($uri, '/'), '/') ?: '/';
	}

	
	/**
	 * Get the valid attributes from the options.
	 *
	 * @param  array   $options
	 * @return string
	 */
	public static function formatGroupClass($new, $old) {
		
		if(isset($new['class'])) {
			
			$classes = trim(trim(array_get($old, 'class')) . ' ' . trim(array_get($new, 'class')));
			
			return implode(' ', array_unique(explode(' ', $classes)));
		}
		return array_get($old, 'class');

	}

	/**
	 * Get the valid attributes from the options.
	 *
	 * @param  array   $options
	 * @return string
	 */
	public function getAttributes($options = array())
	{
		if(is_array($options)) {
			
			if( count($this->groupStack) > 0 ) {
				$options = $this->mergeWithLastGroup($options);
			}

			return array_except($options, $this->reserved);
		}

		return array();
	}

	/**
	 * Get the form action from the options.
	 *
	 * @return string
	 */
	public function dispatch($options)
	{
		// We will also check for a "route" or "action" parameter on the array so that
		// developers can easily specify a route or controller action when creating the
		// menus.
		if (isset($options['url']))
		{
			return $this->getUrl($options);
		}

		elseif (isset($options['route']))
		{
			return $this->getRoute($options['route']);
		}

		// If an action is available, we are attempting to point the link to controller
		// action route. So, we will use the URL generator to get the path to these
		// actions and return them from the method. Otherwise, we'll use current.
		elseif (isset($options['action']))
		{
			return $this->getControllerAction($options['action']);
		}

		return null;
	}

	/**
	 * Get the action for a "url" option.
	 *
	 * @param  array|string  $options
	 * @return string
	 */
	protected function getUrl($options)
	{
		foreach($options as $key => $value) {
			$$key = $value;
		}
		
		$secure = (isset($options['secure']) && $options['secure'] === true) ? true : false;

		if (is_array($url))
		{
			if( self::isAbs($url[0]) ){

				return $url[0];

			}

			return $this->url->to($prefix . '/' . $url[0], array_slice($url, 1), $secure);
		}
		
		if( self::isAbs($url) ){

			return $url;

		}
		return $this->url->to($prefix . '/' . $url, array(), $secure);
	}

	/**
	 * Check if the given url is an absolute url.
	 *
	 * @param  string  $url
	 * @return boolean
	 */
	public static function isAbs($url)
	{
		return parse_url($url, PHP_URL_SCHEME) or false;		
	}

	/**
	 * Get the action for a "route" option.
	 *
	 * @param  array|string  $options
	 * @return string
	 */
	protected function getRoute($options)
	{
		if (is_array($options))
		{
			return $this->url->route($options[0], array_slice($options, 1));
		}

		return $this->url->route($options);
	}

	/**
	 * Get the action for an "action" option.
	 *
	 * @param  array|string  $options
	 * @return string
	 */
	protected function getControllerAction($options)
	{
		if (is_array($options))
		{
			return $this->url->action($options[0], array_slice($options, 1));
		}

		return $this->url->action($options);
	}

	/**
	 * Returns items with no parent
	 *
	 * @return \Illuminate\Support\Collection
	 */
	public function roots()
	{
		return $this->whereParent();
	}

	/**
	 * Filter menu items by user callbacks
	 *
	 * @param  callable $callback
	 *
	 * @return Lavary\Menu\Builder
	 */
	public function filter($callback)
	{
		if( is_callable($callback) ) {
	
			$this->items = $this->items->filter($callback);
	
		}

		return $this;
	}

	/**
	 * Sorts the menu based on user's callable
	 *
	 * @param string|callable $sort_type
	 * @return Lavary\Menu\Builder
	 */
	public function sortBy($sort_by, $sort_type = 'asc'){

		if(is_callable($sort_by)) {

			$rslt = call_user_func($sort_by, $this->items->toArray());

			if(!is_array($rslt)) {
				$rslt = array($rslt);
			}

			$this->items = new Collection($rslt);

		}
		
		// running the sort proccess on the sortable items
		$this->items->sort(function ($f, $s) use ($sort_by, $sort_type) {
			
			$f = $f->$sort_by;
			$s = $s->$sort_by;
			
			if( $f == $s ) {
				return 0;
			}

			if( $sort_type == 'asc' ) { 
				return $f > $s ? 1 : -1;
			}
			
			return $f < $s ? 1 : -1;	

		});

		return $this;

	}

	
	/**
	 * Generate the menu items as list items using a recursive function
	 *
	 * @param string $type
	 * @param int $parent
	 * @return string
	 */
	public function render($type = 'ul', $parent = null)
	{
		$items = '';
		$item_tag = in_array($type, array('ul', 'ol')) ? 'li' : $type;
		
		foreach ($this->whereParent($parent) as $item)
		{
			$items  .= "<{$item_tag}{$this->attributes($item->attr)}>";

			if($item->link) {
				$items .= "<a{$this->attributes($item->link->attr())} href=\"{$item->url()}\">{$item->title()}</a>";
			} else {
				$items .= $item->title;
			}
					
			if( $item->hasChilderen() ) {
				$items .= "<{$type}>";
				$items .= $this->render($type, $item->id());
				$items .= "</{$type}>";
			}
			
			$items .= "</{$item_tag}>";

			if($item->divider) {
				$items .= "<{$item_tag}{$this->attributes($item->divider)}></{$item_tag}>";
			}
		}

		return $items;
	}
		
	/**
	 * Returns the menu as an unordered list.
	 *
	 * @return string
	 */
	public function asUl($attributes = array())
	{
		return "<ul{$this->html->attributes($attributes)}>{$this->render('ul')}</ul>";
	}

	/**
	 * Returns the menu as an ordered list.
	 *
	 * @return string
	 */
	public function asOl($attributes = array())
	{
		return "<ol{$this->html->attributes($attributes)}>{$this->render('ol')}</ol>";
	}

	/**
	 * Returns the menu as div containers
	 *
	 * @return string
	 */
	public function asDiv($attributes = array())
	{
		return "<div{$this->html->attributes($attributes)}>{$this->render('div')}</div>";
	}

	/**
	 * Returns the menu as view
	 *
	 * @param string $view
	 * @param string $menu
	 * @return string
	 */
	public function asView($view, $name = 'menu')
	{
		return $this->environment->make($view, array($name => $this));
	}

	/**
	 * Convert HTML attributes into "property = value" pairs
	 *
	 * @param array $attributes
	 *
	 * @return string
	 */
	public function attributes($attributes = array()) {

		return $this->html->attributes($attributes);
	}


	/**
	 * Search the menu based on an attribute
	 *
	 * @param string $method
	 * @param array  $args
	 *
	 * @return Lavary\Menu\Item
	 */
	public function __call($method, $args)
	{
		preg_match('/^[W|w]here([a-zA-Z0-9_]+)$/', $method, $matches);
		
		if($matches) {
			$attribute = strtolower($matches[1]);
		} else {
			return false;
		}

		$value     = $args ? $args[0] : null;
		
		return $this->items->filter(function($item) use ($attribute, $value) {

			if ( !property_exists($item, $attribute) )
			{
				return false;
			}
			
			if( $item->$attribute == $value )
			{
				return true;
			} 
			
				return false;
		})->values();

	}

	/**
	 * Returns menu item by name
	 *
	 * @return Lavary\Menu\Item
	 */
	public function __get($prop){
		
		if(property_exists($this, $prop)) {

			return $this->$prop;
		}
		
		return $this->whereNickname($prop)
		
					->first();		
	}

}
