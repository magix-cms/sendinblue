<?php
class plugins_sendinblue_db
{
	/**
	 * @param array $config
	 * @param array $params
	 * @return mixed|null
	 */
	public function fetchData(array $config, array $params = [])
	{
		$sql = '';

		if ($config['context'] === 'all') {
			switch ($config['type']) {
				case 'lists':
					$sql = 'SELECT l.*,lc.*,lang.*
							FROM mc_sendinblue_list AS l
							JOIN mc_sendinblue_content AS lc USING(id_list)
							JOIN mc_lang AS lang ON(lc.id_lang = lang.id_lang)
							WHERE lc.id_lang = :id
							GROUP BY l.id_list';
					break;
                case 'active_lists':
                    $sql = 'SELECT l.*,lc.*
							FROM mc_sendinblue_list AS l
							JOIN mc_sendinblue_content AS lc USING(id_list)
							WHERE lc.active = 1
							GROUP BY l.id_list';
                    break;
				case 'iso_lists':
					$sql = 'SELECT l.*,lc.*,lang.*
							FROM mc_sendinblue_list AS l
							JOIN mc_sendinblue_content AS lc USING(id_list)
							JOIN mc_lang AS lang ON(lc.id_lang = lang.id_lang)
							WHERE lang.iso_lang = :id AND lc.active = 1
							GROUP BY l.id_list';
					break;
				case 'data':
					$sql = 'SELECT l.*,lc.*,lang.*
							FROM mc_sendinblue_list AS l
							JOIN mc_sendinblue_content AS lc USING(id_list)
							JOIN mc_lang AS lang ON(lc.id_lang = lang.id_lang)
							WHERE l.id_list = :id';
					break;
			}

			return $sql ? component_routing_db::layer()->fetchAll($sql, $params) : null;
		}
		elseif ($config['context'] === 'one') {
			switch ($config['type']) {
				case 'api':
					$sql = 'SELECT * FROM mc_sendinblue ORDER BY id_api DESC LIMIT 0,1';
					break;
				case 'root':
					$sql = 'SELECT * FROM mc_sendinblue_list ORDER BY id_list DESC LIMIT 0,1';
					break;
				case 'content':
					$sql = 'SELECT * FROM `mc_sendinblue_content` WHERE `id_list` = :id_list AND `id_lang` = :id_lang';
					break;
			}

			return $sql ? component_routing_db::layer()->fetch($sql, $params) : null;
		}
	}

	/**
	 * @param array $config
	 * @param array $params
	 * @return bool|string
	 */
	public function insert(array $config, array $params = [])
	{
		if (!is_array($config)) return '$config must be an array';

		$sql = '';

		switch ($config['type']) {
			case 'list':
				$sql = 'INSERT INTO `mc_sendinblue_list`(id_api, list_id) 
						VALUES (:id_api,:list_id)';
				break;
			case 'content':
				$sql = 'INSERT INTO `mc_sendinblue_content`(id_list,id_lang,name_list,active) 
						VALUES (:id_list,:id_lang,:name_list,:active)';
				break;
		}

		if($sql === '') return 'Unknown request asked';

		try {
			component_routing_db::layer()->insert($sql,$params);
			return true;
		}
		catch (Exception $e) {
			return 'Exception reçue : '.$e->getMessage();
		}
	}

	/**
	 * @param array $config
	 * @param array $params
	 * @return bool|string
	 */
	public function update(array $config, array $params = [])
	{
		if (!is_array($config)) return '$config must be an array';

		$sql = '';

		switch ($config['type']) {
			case 'api':
				$sql = 'UPDATE mc_sendinblue SET api_key = :key WHERE id_api = :id';
				break;
			case 'content':
				$sql = 'UPDATE mc_sendinblue_content 
							SET 
								name_list = :name_list,
								active = :active
							WHERE id_list = :id_list 
							AND id_lang = :id_lang';
				break;
		}

		if($sql === '') return 'Unknown request asked';

		try {
			component_routing_db::layer()->update($sql,$params);
			return true;
		}
		catch (Exception $e) {
			return 'Exception reçue : '.$e->getMessage();
		}
	}

	/**
	 * @param array $config
	 * @param array $params
	 * @return bool|string
	 */
	public function delete(array $config, array $params = [])
	{
		if (!is_array($config)) return '$config must be an array';
			$sql = '';

			switch ($config['type']) {
				case 'list':
					$sql = 'DELETE FROM mc_sendinblue_list WHERE id_list IN ('.$params['id'].')';
					$params = array();
					break;
			}

		if($sql === '') return 'Unknown request asked';

		try {
			component_routing_db::layer()->delete($sql,$params);
			return true;
		}
		catch (Exception $e) {
			return 'Exception reçue : '.$e->getMessage();
		}
	}
}