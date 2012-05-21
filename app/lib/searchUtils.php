<?php

    /*
     * Copyright 2012 Mathieu "OtaK_" Amiot <m.amiot@otak-arts.com> http://mathieu-amiot.fr/
     *
     * Licensed under the Apache License, Version 2.0 (the "License");
     * you may not use this file except in compliance with the License.
     * You may obtain a copy of the License at
     *
     *      http://www.apache.org/licenses/LICENSE-2.0
     *
     * Unless required by applicable law or agreed to in writing, software
     * distributed under the License is distributed on an "AS IS" BASIS,
     * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
     * See the License for the specific language governing permissions and
     * limitations under the License.
     *
     */

	/**
	 * @package    TakPHPLib
	 * @subpackage Search
	 * @author     Mathieu AMIOT <m.amiot@otak-arts.com>
	 * @copyright  Copyright (c) 2011, Mathieu AMIOT
	 * @version    1.1
     * @changelog
     *      1.1 : Introduction of namespace use
     *      1.0 : Initial release
     *      0.7a : Unstable first version
     * @todo Implement the way to have multiple fields to search keywords in
	 */
    namespace TakPHPLib\Search;
	require_once dirname(__FILE__) . '/../cfg/define.php';


	/**
	 * @package    TakPHPLib
	 * @subpackage Search
     *     Generic search engine
	 */
	class searchUtils
	{
		private
				$_joins,
				$_searchFields,
				$_displayedFields,
				$_keywordDbField,
				$_keywords,
				$_resultLinkId,
				$_resultLinkHref,
				$_results,
				$_counts,
				$_limits;

		/**
		 * Ctor
		 * @param array $displayedFields
		 * @param array $searchFields
		 * @param array $keywords
		 */
		public function __construct(array $displayedFields = array(), array $searchFields = array(), array $keywords = array())
		{
			$this->_searchFields    = $searchFields;
			$this->_displayedFields = $displayedFields;
			$this->_keywords        = $keywords;
			$this->_results         = $this->_counts = array();
		}

		/**
		 * Adds a search field (in the WHERE part of the query)
		 * @param string $fieldLabel    internal label of the field, not influencing with the sql query
		 * @param string $dbField       name of the field in the DB
		 * @param string $dbTable       table of the field in the DB
		 * @param string $wantedValue   desired value of the field (where $dbField = $wantedValue for example)
		 * @param bool   $multiValues   if $wantedValue is a comma-separated list of values
		 * @return searchUtils
		 */
		public function addSearchField($fieldLabel, $dbField, $dbTable, $wantedValue, $multiValues = false)
		{
			$this->_searchFields[$dbField] = array(
				'label'     => $fieldLabel,
				'value'     => $wantedValue,
				'db'        => $dbField,
				'dbTable'   => $dbTable,
				'multiVal'  => $multiValues
			);
			return $this;
		}

		/**
		 * Removes a search field by its name
		 * @param string $fieldLabel
		 * @return bool
		 */
		public function removeSearchField($fieldLabel)
		{
			if ($res = isset($this->_searchFields[$fieldLabel]))
				unset($this->_searchFields[$fieldLabel]);
			return $res;
		}

		/**
		 * Adds a field to display (in the results array)
		 * @param string      $fieldLabel    displayed label of the field in the table headers
		 * @param string      $dbField       name of the field in DB, can be an expression (calculated field)
		 * @param string      $dbTable       table of the field
		 * @param bool|string $orderPriority rien ou DESC/ASC
		 * @return searchUtils
		 */
		public function addDisplayedField($fieldLabel, $dbField, $dbTable, $orderPriority = false)
		{
			$this->_displayedFields[$dbField] = array(
				'label'     => $fieldLabel,
				'db'        => $dbField,
				'dbTable'   => $dbTable,
				'order'     => $orderPriority
			);
			return $this;
		}

		/**
		 * Gets the number of displayed fields
		 * @return int
		 */
		public function getDisplayedFieldCount()
		{
			return count($this->_displayedFields);
		}

		/**
		 * Add a new search keyword
		 * @param string $word
		 * @return searchUtils
		 */
		public function addKeyword($word)
		{
			if (array_search($word, $this->_keywords) === false)
				$this->_keywords[] = $word;
			return $this;
		}

		/**
		 * Adds an array of keywords
		 * @param array $words
		 * @return searchUtils
		 */
		public function addKeywords(array $words)
		{
			for ($i = 0, $l = count($words); $i < $l; ++$i)
				if (!empty($words[$i])) $this->addKeyword($words[$i]);
			return $this;
		}

		/**
		 * remove a search keyword
		 * @param string $word
		 * @return bool
		 */
		public function removeKeyword($word)
		{
			if (($res = array_search($word, $this->_keywords)) !== false)
			{
				unset($this->_keywords[$res]);
				return true;
			}
			return false;
		}

		/**
		 * sets the keywords DB field to search in
		 * @param string $field
		 * @return searchUtils
		 */
		public function setKeywordsDbField($field)
		{
			$this->_keywordDbField = $field;
			return $this;
		}

		/**
		 * sets the id to use in the standard output and to add to the results
		 * @param string $dbField
		 * @param string $dbTable
		 * @return searchUtils
		 */
		public function setResultLinkId($dbField, $dbTable)
		{
			$this->_resultLinkId = array(
				'db'    => $dbField,
				'table' => $dbTable
			);
			return $this;
		}

		/**
		 * Sets the http link to use on each link of the standard table
		 * @param string $href MUST contain %id% representing the id shown before
		 * @return searchUtils
		 */
		public function setResultLinkHref($href)
		{
			$this->_resultLinkHref = $href;
			return $this;
		}

		/**
		 * @param array $joins
		 * @return searchUtils
		 */
		public function setJoins(array &$joins)
		{
			$this->_joins = $joins;
			return $this;
		}

		/**
		 * Initializes the limits, useful for pagination
		 * @param int $page
		 * @param int $count
		 * @return searchUtils
		 */
		public function setLimits($page = 1, $count = 20)
		{
			$this->_limits = array(
				'offset'    => ($page - 1) * $count,
				'count'     => $count
			);
			return $this;
		}

		/**
		 * Removes the LIMIT statement from query, disables pagination
		 * @return searchUtils
		 */
		public function removeLimits()
		{
			if (isset($this->_limits)) unset($this->_limits);
			return $this;
		}

		/**
		 * Performs the search and returns the results in an array
		 * @param bool $debug put to true if you want to echo the generated query
		 * @return array
		 */
		public function updateResults($debug = false)
		{
			$q = $this->generateQuery();
			if ($debug) echo $q;
			$this->processQuery($q);
			$this->dedupResults();
			$this->reorderResults();
			return $this->getResults();
		}

		/**
		 * Getter for results
		 * @return array
		 */
		public function getResults()
		{
			return $this->_results;
		}

		/**
		 * Processes a query and put its results in the results array
		 * @param       $query
		 * @param array $params
		 * @return bool
		 */
		private function processQuery($query, array $params = array())
		{
			$dbResult = \TakPHPLib\DB\dbMan::get_instance()->query($query, $params);
			if (!$dbResult) return false;

			while ($data = $dbResult->fetch_assoc(true))
				$this->_results[] = $data;
			return true;
		}

		/**
		 * Deduplicates an array of SQL results, and fills in an array of frequency of results ($this->_counts)
		 * following the frequency of the results, useful for the search results (most frequent on top, the others lower)
		 * @return void
		 */
		private function dedupResults()
		{
			for ($i = 0; $i < count($this->_results); ++$i)
			{
				if (isset($this->_results[$i])) $this->_counts[$i] = 0;
				for ($j = $i + 1, $l = count($this->_results); $j < $l; ++$j)
				{
					if ($this->_results[$i] == $this->_results[$j])
					{
						unset($this->_results[$j]);
						ksort($this->_results);
						++$this->_counts[$i];
					}
				}
			}
			asort($this->_counts, SORT_NUMERIC | SORT_DESC); // tri par pertinence décroissante
		}

		/**
		 * Reorders the results with the help of the counts array
		 * @return void
		 */
		private function reorderResults()
		{
			if (!array_sum($this->_counts)) return;
			$tmp = array(); // init array temporaire
			foreach ($this->_counts as $i => $cpt) // réorganisation des résultats en fonction de la pertinence
				$tmp[] = $this->_results[$i];
			$this->_results = $tmp; // réassignation des résultats vers le bon array
		}

		/**
		 * Generates a search SQL query
		 * @return string
		 */
		private function generateQuery()
		{
			$q      = '';
			$tables = array();

			$select = 'SELECT ';
			$order  = array();
			// Constructing the SELECT statement
			if (isset($this->_resultLinkId)) // if we have an id field
				$select .= "{$this->_resultLinkId['table']}.{$this->_resultLinkId['db']}, ";

			foreach ($this->_displayedFields as $i => $val)
			{
				if (!empty($this->_displayedFields[$i]['db']))
				{
					$tmp = $this->_displayedFields[$i]['dbTable'] . '.' . $this->_displayedFields[$i]['db'];
					if (strpos($this->_displayedFields[$i]['db'], 'date') !== false)
						$select .= "UNIX_TIMESTAMP({$tmp}) AS {$this->_displayedFields[$i]['db']}, ";
					else
						$select .= "{$tmp}, ";

					if ($this->_displayedFields[$i]['order'] !== false)
						$order[$this->_displayedFields[$i]['db']] = $this->_displayedFields[$i]['order'];

					if (!in_array($val['dbTable'], $tables))
						$tables[] = $val['dbTable'];
				}
			}
			$select = substr($select, 0, -2); // removing the last comma + space
			$q .= $select;

			// WHERE statement
			$where = "";
			foreach ($this->_searchFields as $val)
			{
				if (!in_array($val['dbTable'], $tables))
					$tables[] = $val['dbTable'];

				if (empty($where)) $where .= "\nWHERE ";
				else $where .= "\nOR ";

				if (!$val['multiVal']) $where .= "{$val['dbTable']}.{$val['db']} = '{$val['value']}'";
				else $where .= "{$val['dbTable']}.{$val['db']} IN ({$val['value']})";
			}

			// FROM statement
			$from = "\nFROM ";
			$i    = 0;
			foreach ($this->_joins as $table => $using)
			{
				if (in_array($table, $tables))
				{
					if (!$i++) $from .= "{$table}\n";
					else $from .= "JOIN {$table} USING({$using})\n";
				}
			}
			$q .= $from;

			// Keywords inside WHERE statement
			$l = count($this->_keywords);
			if ($l && !empty($this->_keywordDbField))
			{
				$emptyWhere = empty($where);
				if (!$emptyWhere) $where .= "\n AND (\n";
				else $where = "\nWHERE ";
				for ($i = 0; $i < $l; ++$i)
				{
					if ($i) $where .= "OR ";
					$where .= "{$this->_keywordDbField} LIKE '%{$this->_keywords[$i]}%'\n";
				}
				if (!$emptyWhere) $where .= ")\n";
			}
			$q .= $where;

			// ORDER BY statement
			if (count($order))
			{
				$q .= "\nORDER BY ";
				foreach ($order as $fieldName => $orderType)
					$q .= "{$fieldName} {$orderType}, ";
				$q = substr($q, 0, -2);
			}

			// LIMIT statement
			if (isset($this->_limits))
				$q .= "\nLIMIT {$this->_limits['offset']}, {$this->_limits['count']}";

			return $q;
		}

		/**
		 * Ouputs a standard table of results
		 * @return string
		 */
		public function standardOutput()
		{
			$res = $this->standardOutputHeaders();
			if (($l = count($this->_results)))
			{
				for ($i = 0; $i < $l; ++$i)
				{
					$res .= "<tr>" . PHP_EOL;
					foreach ($this->_displayedFields as $db => $curField)
					{
						if (strpos($db, 'date') !== false)
							$displayedField = strftime("%d %B %Y", $this->_results[$i][$db]);
						else
							$displayedField = $this->_results[$i][$db];

						$tmp = str_replace('%id%', $this->_results[$i][$this->_resultLinkId['db']], $this->_resultLinkHref);
						$res .= "<td><a href='{$tmp}'>{$displayedField}</a></td>" . PHP_EOL;
					}
					$res .= "</tr>" . PHP_EOL;
				}
			}
			else
			{
				$c = count($this->_displayedFields);
				$res .= "<tr>" . PHP_EOL . "<td colspan='{$c}'>Pas de résultats !</td>" . PHP_EOL . "</tr>";
			}

			$res .= $this->standardOutputEnd();
			return $res;
		}

		/**
		 * Ouputs the th part of the standard output, in case of customized tabled output
	     * @return string
		 */
		public function standardOutputHeaders()
		{
			$res = PHP_EOL . "<table>" . PHP_EOL . "<thead>" . PHP_EOL;
			foreach ($this->_displayedFields as $curField)
				$res .= "<th>{$curField['label']}</th>" . PHP_EOL;
			$res .= "</thead>" . PHP_EOL . "<tbody>" . PHP_EOL;
			return $res;
		}

		/**
		 * Outputs the end of the standard table
		 * @return string
		 */
		public function standardOutputEnd()
		{
			return "</tbody>" . PHP_EOL . "</table>" . PHP_EOL;
		}
	}
