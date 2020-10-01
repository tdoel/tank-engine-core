<?php
class te_view Extends te_reply
{
  protected $template = null;
  protected $subviews = [];
  public $data = [];
  public $anchor = null;

  public function __construct($template)
  {
    $filename = "views/".$template.".php";
    if(te_file_exists($filename))
    {
      $this->template = $template;
    }
    else
    {
      $this->template = null;
      tank_engine::throw(WARNING,"View template '" . $template . "' is not available");
    }
  }
  public function add_subview($anchor,$view)
  {
    //anchor is where the view is rendered in de view code
    //view is the te_view object that holds the actual view
    $view->anchor = $anchor;
    $this->subviews[$anchor] = $view;
  }
  public function set_topview($anchor)
  {
    //unsets the $anchor variable of the subview at anchor
    //the result is that the surrounding DIV tags will not be
    //printed anymore
    $this->subviews[$anchor]->anchor = null;
  }

  public function render_reply()
  {
    //if AJAX request, then achor should be available
    $anchor = null;
    if(isset($_GET["anchor"]))
    {
      $anchor = $_GET["anchor"];
      // FIXME: GET validation
    }
    if(isset($_POST["anchor"]))
    {
      $anchor = $_POST["anchor"];
      // FIXME: POST validation
    }

    if($anchor)
    {
      //if anchor is set, specify subview as topview by removing
      //its knowledge of its anchor
      $this->set_topview($anchor);

      //as we are rendering AJAX, we want to catch output and pass it as a
      //variable rather than plain text
      ob_start();
    }

    //render the actual template
    $success = $this->render($anchor);

    if($anchor)
    {
      //catch output
      $json_output["html"] = ob_get_clean();

      //list required css / js that may or may not have been loaded previously
      $json_output["css"] = tank_engine::get_css(false);
      $json_output["js"] = tank_engine::get_js(false);

      //list errors
      $json_output["errors"] = tank_engine::get_errors_associative();

      //ajax request may need to load additional parts of the page.
      foreach ($this->ajax_additional_anchors as $anchor)
      {
        $this->set_topview($anchor);
        ob_start();
        $this->render($anchor);
        $json_output["additional_anchor_html"][$anchor] = ob_get_clean();
      }

      if($this->ajax_pushstate != "")
      {
        $json_output["pushstate"] = $this->ajax_pushstate;
      }

      //pass JSON encoded output
      echo json_encode($json_output);
    }
    return $success;
  }

  //this function outputs the actual template
  public function render($anchor = null)
  {
    if($anchor != null)
    {
      //call for subview, get template from stored values
      if(isset($this->subviews[$anchor]))
      {
        //pass data on to subview
        $this->subviews[$anchor]->data += $this->data;
        foreach($this->subviews as $foreach_anchor => $subview)
        {
          if($foreach_anchor != $anchor)
          {
            $this->subviews[$anchor]->add_subview($foreach_anchor,$subview);
          }
        }

        //render subview
        return $this->subviews[$anchor]->render();

      }
      else {
        //call to unexisting $subview
        tank_engine::throw(ERROR,"Subview " . $this->template . " not set.");
        return false;
      }
    }
    else
    {
      $filename = "/views/".$this->template.".php";
      if(te_file_exists($filename))
      {
        echo $this->anchor ? '<div id="'.$this->anchor.'">' : '';
        require te_get_absolute_path($filename);
        echo $this->anchor ? '</div>' : '';
        return true;
      }
      else
      {
        tank_engine::throw(ERROR,"View template " . $this->template . " does not exist.");
        return false;
      }
    }
  }
  public function link($href, $text = null, $ajax_anchor = null, $form_id = null, $classes = [])
  {
    //put a slash in front of it if neccesary
    if(strpos($href,"/") !== 0)
    {
      $href = "/".$href;
    }

    $str_class = 'class="';
    foreach ($classes as $class) {
      $str_class .= $class . " ";
    }
    $str_class .= '"';

    if($text)
    {
      if($ajax_anchor)
      {
        if($form_id)
        {
          return '<a href="" id="a_'.$form_id.'" onclick="mvc.ajax_load_page(\''.TE_URL_ROOT.$href.'\',\''.$ajax_anchor.'\',$(\'#'.$form_id.'\').serializeJSON()); return false" '.$str_class.'>'.$text.'</a>';
        }
        else
        {
          return '<a href="" onclick="mvc.ajax_load_page(\''.TE_URL_ROOT.$href.'\',\''.$ajax_anchor.'\'); return false" '.$str_class.'>'.$text.'</a>';
        }
      }
      else
      {
        return '<a href="'.TE_URL_ROOT.$href.'" '.$str_class.'>'.$text.'</a>';
      }
    }
    else
    {
      return TE_URL_ROOT.$href;
    }

  }
  public function path($href)
  {
    //put a slash in front of it if neccesary
    if(strpos($href,"/") !== 0)
    {
      $href = "/".$href;
    }
    return TE_URL_ROOT . $href;
  }
}
?>
