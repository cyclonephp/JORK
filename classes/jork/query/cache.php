<?php

/**
 * @author Bence Eros <crystal@cyclonephp.com>
 * @package JORK
 */
class JORK_Query_Cache {

    private static $_instances = array();

    /**
     *
     * @param string $class
     * @return JORK_Query_Cache
     */
    public static function inst($class) {
        if ( ! array_key_exists($class, self::$_instances)) {
            self::$_instances[$class] = new JORK_Query_Cache($class);
        }
        return self::$_instances[$class];
    }

    public static function clear_pool() {
        self::$_instances = array();
    }

    /**
     * The class name the query cache belongs to.
     *
     * @var string
     */
    private $_class;

    /**
     * Mapping schema for $this->_class
     *
     * @var JORK_Mapping_Schema
     */
    private $_schema;

    /**
     * An array of INSERT queries that are used to persist the atomic properties
     * of $this->_class instances. Contains one query / table. Note that the atomic
     * properties of an entity can be stored in more than one tables if the
     * entity has got secondary tables.
     *
     * @var array<DB_Query_Insert>
     * @see JORK_Model_Abstract::insert()
     */
    private $_insert_sql;

    /**
     * An array of UPDATE statements used to update the instances
     * of $this->_class.
     *
     * @var array<DB_Query_Update>
     * @see JORK_Model_Abstract::update()
     */
    private $_update_sql;

    /**
     * An array of DELETE statements used to delete the instances
     * of $this->_class.
     *
     * @var array<DB_Query_Delete>
     * @see JORK_Model_Abstract::delete()
     */
    private $_delete_sql;

    private function  __construct($class) {
        $this->_class = $class;
        $this->_schema = JORK_Model_Abstract::schema_by_class($class);
    }

    /**
     * Generates $this->_insert_sql if not generated already
     *
     * @return array<DB_Query_Insert>
     */
    public function insert_sql() {
        if (NULL === $this->_insert_sql) {
            $primary_tbl_ins = new DB_Query_Insert;
            $primary_tbl_ins->table = $this->_schema->table;
            $this->_insert_sql = array($this->_schema->table => $primary_tbl_ins);
            if ($this->_schema->secondary_tables != NULL) {
                foreach ($this->_schema->secondary_tables as $sec_table => $join_metadata) {
                    $ins_sql = new DB_Query_Insert();
                    $ins_sql->table = $sec_table;
                    $this->_insert_sql [$sec_table] = $ins_sql;
                }
            }
        }
        return $this->_insert_sql;
    }

    /**
     * Generates $this->_update_sql if not generated already
     *
     * @return array<DB_Query_Update>
     */
    public function update_sql() {
        if (NULL === $this->_update_sql) {
            $prim_tbl_upd = new DB_Query_Update;
            $prim_tbl_upd->table = $this->_schema->table;
            $this->_update_sql = array(
                $this->_schema->table => $prim_tbl_upd
            );
            if ($this->_schema->secondary_tables != NULL) {
                foreach ($this->_schema->secondary_tables as $sec_table => $join_metadata) {
                    $upd_sql = new DB_Query_Update;
                    $upd_sql->table = $sec_table;
                    $this->_update_sql[$sec_table] = $upd_sql;
                }
            }
        }
        return $this->_update_sql;
    }

    public function delete_sql() {
        if (NULL === $this->_delete_sql) {
            $prim_tbl_del = new DB_Query_Delete;
            $prim_tbl_del->table = $this->_schema->table;
            $prim_tbl_del->conditions = array(
                new DB_Expression_Binary($this->_schema->primary_key(), '=', NULL)
            );
            $this->_delete_sql = array($prim_tbl_del);
            if ($this->_schema->secondary_tables != NULL) {
                foreach ($this->_schema->secondary_tables as $tbl_name => $tbl_def) {
                    if (array_key_exists('on_delete', $tbl_def)
                            && $tbl_def['on_delete'] === JORK::CASCADE) {
                        $del_stmt = new DB_Query_Delete;
                        $del_stmt->table = $tbl_name;
                        $del_stmt->conditions = array(
                            new DB_Expression_Binary($tbl_def['join_column'], '=', NULL)
                        );
                        $this->_delete_sql [] = $del_stmt;
                    }
                }
            }
        }
        return $this->_delete_sql;
    }

}
