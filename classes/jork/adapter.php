<?php

/**
 * Adapter interface.
 *
 * An adapter is responsible for mapping JORK queries to SimpleDB queries.
 * 
 * @author Bence Eros <crystal@cyclonephp.com>
 * @package JORK 
 */
interface JORK_Adapter {

    /**
     * @return JORK_Query_Result
     */
    public function exec_select(JORK_Query_Select $select);
    
}
