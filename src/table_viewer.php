<?php
require_once("link_helper.php");
define("SORT_ASCEND", 1);
define("SORT_DESCEND", 0);

class SortDefinition
{
  function __construct($column, $ascending = SORT_ASCEND)
  {
    $this->column = $column;
    $this->ascending = $ascending;
  }

  function render()
  {
    return $this->column.($this->ascending ? "" : " DESC");
  }

  public $column;
  public $ascending;
};

class SortBuilder
{
  public function add($sortDefinition)
  {
    if (empty($this->result))
      $this->result = " ORDER BY \n";
    else
      $this->result .= ",\n";
    $this->result .= $sortDefinition->render();
  }
  public $result;
}

class SqlFromFiller
{
  public function add($sql, $as = NULL)
  {
    if (!empty($this->result))
      $this->result .= ",\n";
    $this->result .= $sql;
    if ($as)
      $this->result .= " as ".$as;
  }
  public $result;
};

class TableColumn
{
  function __construct($name, $caption, $sql, $cellFiller, $cellParameters, $get, $defaultSortAscend = SORT_ASCEND)
  {
    $this->name = $name;
    $this->caption = $caption;
    $this->sql = $sql;
    $this->cellFiller = $cellFiller;
    $this->cellParameters = $cellParameters;
    $this->get = $get;
    $this->defaultSortAscend = $defaultSortAscend;
  }

  public function fillFrom(&$sqlFromFiller)
  {
    foreach ($this->sql as $part)
      $sqlFromFiller->add($part[0], $part[1]);
  }

  public function renderHeader($currentSort)
  {
    echo "<th>";
    $url = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
    $getCopy = $this->get;
    $thisIsSorted = $currentSort->column == $this->name;
    $getCopy["sort"] = $this->name;
    if ($thisIsSorted)
      $getCopy["d"] = $currentSort->ascending ? "down" : "up";
    else
      $getCopy["d"] = $this->defaultSortAscend ? "up" : "down";

    echo "<span style=\"vertical-align: middle;\"><a href=\"".generateAddress($url, $getCopy)."\">";
    echo $this->caption;
    echo "</a>";
    if ($thisIsSorted)
    {
      global $resourceAddress;
      echo "<img class=\"sorting-image\" src=\"".$resourceAddress."/img/arrow-".($currentSort->ascending ? "up" : "down").".png\"/>";
    }
    echo "</span>";
    echo "</th>";
  }

  public function renderCell($row)
  {
    echo "<td".$this->getCellParameters().">";
    $filler = $this->cellFiller;
    $filler($row);
    echo "</td>";
  }

  private function getCellParameters()
  {
    if (empty($this->cellParameters))
      return "";
    return " ".$this->cellParameters;
  }

  public function getSort($textualDirection)
  {
    return new SortDefinition($this->sql[0][1], $textualDirection ? ($textualDirection == "up") : $this->defaultSortAscend);
  }

  private $get;
  public $name;
  public $caption;
  public $sql;
  public $cellFiller;
  public $cellParameters;
  public $defaultSortAscend;
};

class TableViewer
{
   public function __construct($queryCore, $get)
  {
    $this->queryCore = $queryCore;
    $this->get = $get;
  }

  public function addColumn($name, $caption, $sql, $cellFiller, $cellParameters = NULL)
  {
    $this->columns[$name] = new TableColumn($name, $caption, $sql, $cellFiller, $cellParameters, $this->get);
    if (@$_GET["sort"] == $name)
      $this->currentSort = $this->columns[$name]->getSort(@$_GET["d"]);
  }

  public function renderHeader()
  {
    echo "<tr>";
    foreach ($this->columns as $column)
      $column->renderHeader($this->currentSort);
    echo "</tr>";
  }

  private function buildSort()
  {
    $sortBuilder = new SortBuilder();
    if ($this->fixedSort)
      $sortBuilder->add($this->fixedSort);
    if ($this->currentSort)
      $sortBuilder->add($this->currentSort);
    if ($this->lastSort)
      $sortBuilder->add($this->lastSort);
    return $sortBuilder->result;
  }

  private function getStart()
  {
    $result = @$this->get["start"];
    if ($result and is_numeric($result))
      return $result;
    return 1;
  }

  private function buildQuery()
  {
    $result = "SELECT \n";
    $sqlFromFiller = new SqlFromFiller();
    foreach ($this->columns as $column)
      $column->fillFrom($sqlFromFiller);
    $result .= $sqlFromFiller->result;
    $result .= " FROM \n";
    $result .= $this->queryCore;
    $result .= $this->buildSort();
    $result .= " LIMIT ".TABLE_PAGE_SIZE;
    $start = $this->getStart();
    if ($start > 1)
      $result .= " OFFSET ".($start - 1);
    return $result;
  }

  private function renderRow($row)
  {
    echo "<tr>";
    foreach ($this->columns as $column)
      $column->renderCell($row);
    echo "</tr>\n";
  }

  public function render()
  {
    echo "<table class=\"data-table\">";
    $data = query($this->buildQuery());
    $total = query("SELECT COUNT(*) as total FROM ".$this->queryCore)->fetch_assoc()["total"];
    if ($data->num_rows < $total)
    {
      echo "<caption>";
      $start = $this->getStart();
      if ($start > 1)
      {
        $url = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
        $getCopy = $this->get;
        $getCopy["start"] = max($start - TABLE_PAGE_SIZE, 1);
        echo "<a href=\"".generateAddress($url, $getCopy)."\">Previous</a> ";
      }

      echo  $start."-".min($start + $data->num_rows, $total)." of ".$total;

      if ($start + $data->num_rows < $total)
      {
        $url = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
        $getCopy = $this->get;
        $getCopy["start"] = $start + TABLE_PAGE_SIZE;
        echo " <a href=\"".generateAddress($url, $getCopy)."\">Next</a> ";
      }
      echo "</caption>";
    }
    $this->renderHeader();
    while ($row = $data->fetch_assoc())
      $this->renderRow($row);
    echo "</table>";
  }

  public function setFixedSort($fixedSort)
  {
    $this->fixedSort = $fixedSort;
  }

  public function setPrimarySort($primarySort)
  {
    $this->currentSort = $primarySort;
  }

  public function setLastSort($lastSort)
  {
    $this->lastSort = $lastSort;
  }

  public $queryCore;
  private $columns;
  private $get;
  private $fixedSort;
  private $currentSort;
  private $lastSort;
};

?>
