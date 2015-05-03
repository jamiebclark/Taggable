# Taggable
Taggable is a Plugin for CakePHP 2.x to add tags to a model

## Installation
1. Add the GitHub repository to your CakePHP app's Plugin folder.
2. Add the Plugin to your `bootstrap.php` file
3. Using the cake console, create your Tag table by running: `cd APP_DIRECTORY; cake schema create --plugin=Taggable`

## Usage
### Taggable Behavior
Use the Taggable Behavior for any model that you want to connect to the Taggable tags table:
```
class YourModel extends AppModel {
  public $name = "YourModel";
  public $actsAs = array('Taggable.Taggable');
}
```
#### Filtering by Tags
You can filter a model query by tags if the model is using the Taggable behavior by including a "tags" field in your query. 
```
$this->find('all', array(
  'tags' => array('news', 'baseball'),
));
```
### Tag Helper
Use the Tag Helper to add and display tags in your model. Include the TagHelper in your Controller without any parameters:
```
...
  public $helpers = array('Taggable.Tag');
...
```
#### Adding Tags
Include `$this->Tag->input("YourModel");` in your form. This will display a form to add new tags and remove existing tags.
#### Displaying Tags
```
echo $this->Tag->tagList($result["YourModel"]);
```
Including a url in the options will make each tag a link:
```
echo $this->Tag->tagList($result["YourModel"], array(
  'url' => array(
    'controller' => 'your_models',
    'action' => 'index',
  )
));
```
Each link will have `tag:TAG_NAME` appended to the url.

Including the boolean value for `'x'` will add an "X" link to each tag, allowing you to remove them from the current url.
