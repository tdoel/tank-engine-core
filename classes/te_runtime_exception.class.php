<?php
// base class for TE specific runtime errors
class te_runtime_exception Extends Exception
{
    public function print_stacktrace()
    {
      global $te_loaded_classes;
      echo "<p>
      <h4>Stacktrace</h4>
      </p>
      <style>td {padding-right: 10px;}</style>
      <table><tr>
      <td>File</td>
      <td>Line</td>
      <td>Class</td>
      <td>Function</td>
      <td>Class used</td>
      </tr>";
      $trace = $this->getTrace();
      for($i = 0; $i < count($trace); $i++)
      {
        if (isset($trace[$i+1]["class"]) && isset($te_loaded_classes[$trace[$i+1]["class"]]) && $te_loaded_classes[$trace[$i+1]["class"]] == "application")
        {
            echo '<tr style="font-weight:bold;">';
        }
        else
        {
          echo "<tr>";
        }
        echo "<td>".(isset($trace[$i]["file"]) ? $trace[$i]["file"] : "-")."</td>";
        echo "<td>".(isset($trace[$i]["line"]) ? $trace[$i]["line"] : "-")."</td>";
        echo "<td>".(isset($trace[$i+1]["class"]) ? $trace[$i+1]["class"] : "-")."</td>";
        echo "<td>".(isset($trace[$i+1]["function"]) ? $trace[$i+1]["function"] : "-")."</td>";
        echo "<td>".(isset($trace[$i+1]["class"]) && isset($te_loaded_classes[$trace[$i+1]["class"]]) ? $te_loaded_classes[$trace[$i+1]["class"]] : "-")."</td>";
        echo "</tr>";
      }
      echo "</table>";
    }
}
