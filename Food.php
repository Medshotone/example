<?php

class Food extends Model
{
    /**
     * @param array $data
     * @return array
     */
    public function getFoods(array $data = array()): array
    {
        $sql = "SELECT * FROM " . DB_PREFIX . "food f LEFT JOIN " . DB_PREFIX . "food_description fd ON (f.food_id = fd.food_id)";

        $sql .= " WHERE fd.language_id = '" . (int)$this->config->get('config_language_id') . "'";

        $sql .= " GROUP BY f.food_id";

        $sort_data = array(
            'fd.title',
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY fd.title";
        }

        if (isset($data['order']) && ($data['order'] == 'DESC')) {
            $sql .= " DESC";
        } else {
            $sql .= " ASC";
        }

        if (isset($data['start']) || isset($data['limit'])) {
            if ($data['start'] < 0) {
                $data['start'] = 0;
            }

            if ($data['limit'] < 1) {
                $data['limit'] = 20;
            }

            $sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
        }

        $query = $this->db->query($sql);

        return $query->rows;
    }

    /**
     * @param array $data
     * @return int
     */
    public function getTotalFoods(array $data = array()): int
    {
        $sql = "SELECT COUNT(f.food_id) AS total FROM " . DB_PREFIX . "food f LEFT JOIN " . DB_PREFIX . "food_description fd ON (f.food_id = fd.food_id)";

        $sql .= " WHERE fd.language_id = '" . (int)$this->config->get('config_language_id') . "'";

        if (!empty($data['filter_title'])) {
            $sql .= " AND fd.title LIKE '" . $this->db->escape($data['filter_title']) . "%'";
        }

        $query = $this->db->query($sql);

        return $query->row['total'];
    }

    /**
     * @param int $food_id
     * @return array
     */
    public function getFood(int $food_id): array
    {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "food WHERE food_id = '{$food_id}'");

        return $query->row;
    }

    /**
     * @param int $food_id
     * @return array
     */
    public function getFoodDescriptions(int $food_id): array
    {
        $food_description_data = array();

        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "food_description WHERE food_id = '{$food_id}'");

        foreach ($query->rows as $result) {
            $food_description_data[$result['language_id']] = array(
                'title'              => $result['title'],
                'sub_title'          => $result['sub_title'],
                'time'               => $result['time'],
                'kcal'               => $result['kcal'],
                'squirrels_gram'     => $result['squirrels_gram'],
                'fats_gram'          => $result['fats_gram'],
                'carbohydrates_gram' => $result['carbohydrates_gram'],
                'ingredients'        => $result['ingredients'],
                'cooking_methods'    => $result['cooking_methods']
            );
        }

        return $food_description_data;
    }

    /**
     * @param array $data
     * @return int
     */
    public function addFood(array $data): int
    {
        //need set image = '' for insert working
        $this->db->query("INSERT INTO " . DB_PREFIX . "food SET image = ''");

        $food_id = (int)$this->db->getLastId();

        if (isset($data['image'])) {
            $this->db->query("UPDATE " . DB_PREFIX . "food SET image = '{$this->db->escape($data['image'])}' WHERE food_id = '{$food_id}'");
        }

        foreach ($data['food_description'] as $language_id => $value) {
            //$language_id must be int
            $language_id = (int)$language_id;

            $this->db->query("
                INSERT INTO " . DB_PREFIX . "food_description
                SET food_id = '{$food_id}',
                    language_id = '{$language_id}',
                    title = '{$this->db->escape($value['title'])}',
                    sub_title = '{$this->db->escape($value['sub_title'])}',
                    time = '{$this->db->escape($value['time'])}',
                    kcal = '{$this->db->escape($value['kcal'])}',
                    squirrels_gram = '{$this->db->escape($value['squirrels_gram'])}',
                    fats_gram = '{$this->db->escape($value['fats_gram'])}',
                    carbohydrates_gram = '{$this->db->escape($value['carbohydrates_gram'])}',
                    ingredients = '{$this->db->escape($value['ingredients'])}',
                    cooking_methods = '{$this->db->escape($value['cooking_methods'])}'
            ");
        }

        return $food_id;
    }

    /**
     * @param int $food_id
     * @param array $data
     * @return int
     */
    public function editFood(int $food_id, array $data): int
    {
        if (isset($data['image'])) {
            $this->db->query("UPDATE " . DB_PREFIX . "food SET image = '{$this->db->escape($data['image'])}' WHERE food_id = '{$food_id}'");
        }

        foreach ($data['food_description'] as $language_id => $value) {
            //$language_id must be int
            $language_id = (int)$language_id;

            $this->db->query("
                UPDATE " . DB_PREFIX . "food_description
                SET title = '{$this->db->escape($value['title'])}',
                    sub_title = '{$this->db->escape($value['sub_title'])}',
                    time = '{$this->db->escape($value['time'])}',
                    kcal = '{$this->db->escape($value['kcal'])}',
                    squirrels_gram = '{$this->db->escape($value['squirrels_gram'])}',
                    fats_gram = '{$this->db->escape($value['fats_gram'])}',
                    carbohydrates_gram = '{$this->db->escape($value['carbohydrates_gram'])}',
                    ingredients = '{$this->db->escape($value['ingredients'])}',
                    cooking_methods = '{$this->db->escape($value['cooking_methods'])}'
                WHERE food_id = '{$food_id}' AND language_id = '{$language_id}'
            ");
        }

        return $food_id;
    }

    /**
     * @param int $food_id
     */
    public function copyFood(int $food_id)
    {
        $food_info = $this->getFood($food_id);

        if (!empty($food_info)) {
            $food_info['food_description'] = $this->getFoodDescriptions($food_id);

            $food_info['food_description'][(int)$this->config->get('config_language_id')]['title'] = '(copy) ' . $food_info['food_description'][(int)$this->config->get('config_language_id')]['title'];

            $this->addFood($food_info);
        }
    }

    /**
     * @param int $food_id
     */
    public function deleteFood(int $food_id)
    {
        $this->db->query("DELETE FROM " . DB_PREFIX . "food WHERE food_id = '{$food_id}'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "food_description WHERE food_id = '{$food_id}'");
    }
}
