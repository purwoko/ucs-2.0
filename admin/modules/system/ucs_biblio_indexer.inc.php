<?php
/**
 * Copyright (C) 2010  Wardiyono (wynerst@gmail.com), Arie Nugraha (dicarve@yahoo.com)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 */

class ucs_biblio_indexer
{
	public $total_records = 0;
	public $indexed = 0;
	public $failed = array();
	public $errors = array();
	public $indexing_time = 0;
	private $exclude = array();
	private $obj_db = false;
	private $verbose = false;

	public function __construct($obj_db, $bool_verbose = false) {
		$this->obj_db = $obj_db;
		$this->verbose = $bool_verbose;
	}


	/**
	 * Creating full index database of bibliographic records
	 * @param	boolean		$bool_empty_first: Emptying current index first
	 * @return	void
	 */
	public function createFullIndex($bool_empty_first = false) {
		if ($bool_empty_first) {
			$this->emptyingIndex();
		}
		$bib_sql = 'SELECT biblio_id FROM biblio';
		// query
		$rec_bib = $this->obj_db->query($bib_sql);
		$r = 0;
		if ($rec_bib->num_rows > 0) {
			// start time counter
			$_start = function_exists('microtime')?microtime(true):time();
			$this->total_records = $rec_bib->num_rows;
			// loop records and create index
			while ($rb_id = $rec_bib->fetch_row()) {
				$biblio_id = $rb_id[0];
				$index = $this->makeIndex($biblio_id);
			}
			// get end time
			$_end = function_exists('microtime')?microtime(true):time();
			$this->indexing_time = $_end-$_start;
		}
	}


	/**
	 * Emptying index table
	 * @return	boolean		true on success, false otherwise
	 */
	public function emptyingIndex() {
		@$this->obj_db->query('TRUNCATE TABLE `search_biblio`');
		if ($this->obj_db->errno) {
			$this->errors[] = $this->obj_db->error;
			return false;
		}
		return true;
	}


	/**
	 * Make index for one bibliographic record
	 * @param	int		$int_biblio_id: ID of biblio to index
	 * @return	boolean	false on Failed, true otherwise
	 */
	public function makeIndex($int_biblio_id) {
		$bib_sql = 'SELECT b.biblio_id, b.title, b.edition, b.publish_year, b.notes, b.series_title, b.classification, b.spec_detail_info,
			g.gmd_name AS `gmd`, pb.publisher_name AS `publisher`, pl.place_name AS `publish_place`, b.isbn_issn,
			lg.language_name AS `language`, b.call_number, b.opac_hide, b.promoted, b.labels, b.`collation`, b.image, b.node_id, b.input_date, b.last_update
			FROM biblio AS b
			LEFT JOIN mst_gmd AS g ON b.gmd_id = g.gmd_id
			LEFT JOIN mst_publisher AS pb ON b.publisher_id = pb.publisher_id
			LEFT JOIN mst_place AS pl ON b.publish_place_id = pl.place_id
			LEFT JOIN mst_language AS lg ON b.language_id = lg.language_id WHERE b.biblio_id='.$int_biblio_id;
		// query
		$rec_bib = $this->obj_db->query($bib_sql);

		if ($rec_bib->num_rows < 1) {
			return false;
		} else {
			$rb_id = $rec_bib->fetch_assoc();
		}

		if ($this->verbose) { echo 'Indexing: '.$rb_id['title'].'...'; }
		$data['biblio_id'] = $int_biblio_id;

		/* GMD , Title, Year  */
		$data['title'] = $this->obj_db->escape_string($rb_id['title']);
		$data['edition'] = $this->obj_db->escape_string($rb_id['edition']);
		$data['gmd'] = $this->obj_db->escape_string($rb_id['gmd']);
		$data['publisher'] = $this->obj_db->escape_string($rb_id['publisher']);
		$data['publish_place'] = $this->obj_db->escape_string($rb_id['publish_place']);
		$data['isbn_issn'] = $this->obj_db->escape_string($rb_id['isbn_issn']);
		$data['language'] = $this->obj_db->escape_string($rb_id['language']);
		$data['year'] = $rb_id['publish_year'];
		$data['classification'] = $this->obj_db->escape_string($rb_id['classification']);
		$data['spec_detail_info'] = $this->obj_db->escape_string($rb_id['spec_detail_info']);
		$data['call_number'] = $this->obj_db->escape_string($rb_id['call_number']);
		$data['opac_hide'] = $rb_id['opac_hide'];
		$data['promoted'] = $rb_id['promoted'];
		if ($rb_id['labels']) {
			$_labels = unserialize($rb_id['labels']);
			if (is_array($_labels) && count($_labels) > 0) {
				$data['labels'] = @implode(' - ', $_labels);
			} else {
				$data['labels'] = 'literal{NULL}';
			}
		} else {
			$data['labels'] = 'literal{NULL}';
		}
		$data['collation'] = $this->obj_db->escape_string($rb_id['collation']);
		$data['image'] = $this->obj_db->escape_string($rb_id['image']);
		$data['location'] = $this->obj_db->escape_string($rb_id['node_id'].' - '.$sysconf['node'][$rb_id['node_id']]['name']);
		$data['input_date'] = $rb_id['input_date'];
		$data['last_update'] = $rb_id['last_update'];
		if ($rb_id['notes'] != '') {
			$data['notes'] = trim($this->obj_db->escape_string(strip_tags($rb_id['notes'], '<br><p><div><span><i><em><strong><b><code>')));
		}
		if ($rb_id['series_title'] != '') {
			$data['series'] = $this->obj_db->escape_string($rb_id['series_title']);
		}

		/* author  */
		$au_all = '';
		$au_sql = 'SELECT ba.biblio_id, ba.level, au.author_name AS `name`, au.authority_type AS `type`
			FROM biblio_author AS ba LEFT JOIN mst_author AS au ON ba.author_id = au.author_id
			WHERE ba.biblio_id ='. $int_biblio_id;
		$au_id = $this->obj_db->query($au_sql);
		while($rs_au = $au_id->fetch_assoc()) {
			$au_all .= $rs_au['name'] . ' - ';
		}
		if ($au_all !='') {
			$au_all = substr_replace($au_all, '', -3);
			$data['author'] = $this->obj_db->escape_string($au_all);
		}

		/* subject  */
		$topic_all = '';
		$topic_sql = 'SELECT bt.biblio_id, bt.level, tp.topic, tp.topic_type AS `type`
			FROM biblio_topic AS bt LEFT JOIN mst_topic AS tp ON bt.topic_id = tp.topic_id
			WHERE bt.biblio_id ='. $int_biblio_id;
		$topic_id = $this->obj_db->query($topic_sql);
		while ($rs_topic = $topic_id->fetch_assoc()) {
			$topic_all .= $rs_topic['topic'] . ' - ';
		}
		if ($topic_all != '') {
			$topic_all = substr_replace($topic_all, '', -3);
			$data['topic'] = $this->obj_db->escape_string($topic_all);
		}

		/*  SQL operation object  */
		$sql_op = new simbio_dbop($this->obj_db);

		/*  Insert all variable  */
		if ($sql_op->insert('search_biblio', $data)) {
			if ($this->verbose) { echo " indexed\n"; }
			$this->indexed++;
		} else {
			if ($this->verbose) { echo " FAILED! (Error: '.$sql_op->error.')\n"; }
			$this->failed[] = $int_biblio_id;
			// line below is for debugging purpose only
			// echo '<div>'.$sql_op->error.'</div>';
		}

		return true;
	}


	/**
	 * Update index
	 *
	 * @return	void
	 */
	public function updateFullIndex() {
		$bib_sql = 'SELECT b.biblio_id FROM biblio AS b
			LEFT JOIN search_biblio AS sb ON b.biblio_id = sb.biblio_id
			WHERE sb.biblio_id is NULL';
		// query
		$rec_bib = $this->obj_db->query($bib_sql);
		$r = 0;
		if ($rec_bib->num_rows > 0) {
			// start time counter
			$_start = function_exists('microtime')?microtime(true):time();
			$this->total_records = $rec_bib->num_rows;
			while ($rb_id = $rec_bib->fetch_row()) {
				$biblio_id = $rb_id[0];
				$index = $this->makeIndex($biblio_id);
			}
			// end time
			$_end = function_exists('microtime')?microtime(true):time();
			$this->indexing_time = $_end-$_start;
		}
	}
}
