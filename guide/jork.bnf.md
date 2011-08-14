### JORK EBNF
<pre> 
statement := select_statement | update_statement | insert_statement | delete_statement

select_statement := select_clause? from_clause with_clause*
	join_clause* where_clause? order_by_clause? group_by_clause? having_clause?
	offset_clause? limit_clause?
	
select_clause := 'SELECT' select_list?

select_list := select_list_item (', ' select_list_item)*

select_list_item := ((property_chain property_projection?) | database_expression) (' ' alias_name)?

alias_name := identifier

property_projection := '{' property_chain (',' property_chain)* '}'
	
from_clause := 'FROM' from_list

with_clause := 'WITH' 'ONLY'? with_list

expression := property_chain | database_expression | '(' select_statement ')'

property_chain := identifier ('.' identifier)*

database_expression := [a-zA-Z_-+/*(){} ]+

identifier := [a-zA-Z_]+

from_list = entity_class | entity_class_def (',' entity_class_def)* | property_chain (' ' entity_alias)?

entity_class_def := entity_class (' ' entity_alias)?

entity_class := identifier

entity_alias := identifier

with_list := with_list_item (',' with_list_item)*

with_list_item := (property_chain (' ' entity_alias)?) | '(' select_statement ')'

join_clause := ('LEFT' | 'INNER')? 'JOIN' entity_class_def 'ON' logical_expression

where_clause := 'WHERE' logical_expression

order_by_clause := 'ORDER BY' order_by_item (',' order_by_item)*

group_by_clause := 'GROUP BY' group_by_item (',' group_by_item)*

having_clause := 'HAVING' logical_expression

logical_expression := (comparator_expression | composite_logical_expression | exists_expression) 
	| ( '(' comparator_expression | composite_logical_expression | exists_expression ')' )

comparator_expression := (expression ('=' | '<' | '>' | '<=' | '>=') expression)
	| expression 'NOT'? 'IN' '(' select_statement ')'

composite_logical_expression := logical_expression ('AND' | 'OR') logical_expression

exists_expression := 'NOT'? 'EXISTS' select_statement


order_by_item := property_chain 'ASC' | 'DESC'

group_by_item := expression

offset_clause := 'OFFSET' integer

limit_clause := 'LIMIT' integer

integer := [1-9][0-9]*


update_statement := 'UPDATE' entity_class 'SET' update_list where_clause?

update_list := identifier '=' expression (',' identifier '=' expression)*


insert_statement := 'INSERT INTO' entity_class '(' identifier (', ' identifier)* ')' 
	'VALUES' '('database_expression (', 'database_expression)*')'
	

delete_statement := 'DELETE FROM' entity_class where_clause? limit_clause?
</pre>
