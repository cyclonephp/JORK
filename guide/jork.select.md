## SELECT statements

### Terms used in this document:

<dl>
	<dt>entity class</dt>
	<dd>a mapped class managed by JORK. Also known as model class.</dd>
	
	<dt>entity</dt>
	<dd>an instance of an entity class</dd>
	
	<dt>property</dt>
	<dd>An attribute of an entity class. The entity class is called
		the parent entity class of the property.</dd>
	
	<dt>active property</dt>
	<dd>an active property is a property that has the same value in the
		entity as in the related database row(s).</dd>
	
	<dt>passive property</dt>
	<dd>A passive property is a property that does not exists in the entity
		and it's unknown what is it's value in the related database rows.</dd>
	
	<dt>transient property</dt>
	<dd>A transient property is a property that's value exists in the entity
		but it is not the same as the value in the related database rows
		or the row does not exists.</dd>
		
	<dt>transient entity</dt>
	<dd>A transient entity is a entity that has at least one transient property.</dd>
	
	<dt>atomic property</dt>
	<dd>a property of an entity that can be stored in exactly one 
		database column</dd>
	
	<dt>component</dt>
	<dd>a property that is not an atomic property. The type of a component
		must be an entity class. It means that components are also entities.</dd>
	
	<dt>property chain</dt>
	<dd>a non-empty list of properties. If the query has got an implicit
		root entity class, then the first item of the list should be
		a property of the implicit root entity class. Otherwise the first
		item must be an explicit root entity class. Every N+1th item must
		be a property of the entity class referenced by the Nth item of
		the list (N > 1). Therefore all properties in the property list 
		must be components except the last one.
	</dd>
		
	<dt>implicit root entity class</dt>
	<dd>All property chains in a query have got an implicit root entity
		if the following rescrictions are satisfied:
		
		* the from clause contains exactly one entity class definition
			and it hasn't got an alias.
		* any of the items in the with clauses does not have an alias
		* there are no join clauses in the query
		
		If the implicit root entity class of the query exists then the 
		first items of all property chains in the query should be a 
		property of the implicit root entity class.
		</dd>
		
	<dt>explicit root entity class</dt>
	<dd>If the query does not have an implicit root entity class then 
		every property chains must have an explicit root entity class.
		It is the alias name of an entity previously defined in an
		entity class definition, with list item or join list item.
	</dd>

	<dt>result set</dt>
	<dd>A result set is a result of a SELECT query execution. It's a list
		of entities or a list of tuple of entities. All or some of the
		properties of these entites may be loaded.</dd>
		
	<dt>alias name</dt>
	<dd>an identifier that belongs to an entity class or a property chain
	and can be used in the query instead of its entity class or property chain</dd>
	
</dl>

### The select clause

All select statements should start with the 'SELECT' keyword. After the
'SELECT' keyword there is an optional property list.

If the property list is missing, then

* the from clause should contain exactly one entity class definition
* all the atomic properties of the entity are loaded
* all items of the result list are an entity
	
The property list can contain projected properties. A projected property
is a property chain with an optional list of property chains separated by
commas (,) and the list is surrounded by curly brackets. The projected 
property can be followed by an alias identifier.

example property list: <code>user.name, topic.category{id,name} cat</code>


If the property list exists (has at least one item) then the items of the
result set of the query will be associative arrays that contain an 
appropriate value for each items of the property list. The keys of the
array will be the property aliases or the property chains without the
projection information if the alias is not present.

### The from clause

The from clause of the query should start with the FROM keyword, which
can be followed by:

* exactly one entity class with an optional alias name
* entity class definitions with required alias names
* a property chain with an optional alias, if it is a subquery. The 
	property chain should be a valid property chain of the parent query.

### The with clause
	
The with clauses of the query are lists which's items can be the followings:

* property chains with optional aliases. The properties (defined by
	the property chain) of the root entity of the property chain will be active
	properties after query execution.
	The alias name of the property chain can be used as an identifier in 
	the select, where, order by and having clauses of the query, but the alias 
	name does not affect the query result.
* subqueries. The from clause of the subquery should contain exactly one
	property chain (with an optional alias) which is a valid property 
	chain of the parent query. The optional alias is visible in the 
	subquery and its subqueries, but it is not visible from the parent
	query. If the parent query has got an alias name with the same name,
	then it can't be used in the subquery.
	
	
### The join clause

The join clause can be used to use joins not defined in any entity schema.
After the join clause there should be an entity class with an alias name.
The latter is required in this case. After the alias name comes the 'ON'
clause, then the join conditions. To the join conditions the same rules
apply as to the where condition, see below.

### The where clause

There where clause starts with the 'WHERE' keyword and is followed by a
where condition. There where condition should be a logical expression
defined by the following rules:

* if A and B are logical expressions, then A 'AND' B and A 'OR' B are
logical expressions too.
* if A is a logical expression, then '(' A ')' is a logical expression too.
* if A and B are 


### The order by clause

### The group by clause

### The having clause

### The offset and limit clause

### Database expressions

Database expressions can appear at the following clauses of the JORK query:
* select list
* 

## Query mapping process

1. Determining if there is an implicit root entity

2. Resolving root entities
	* FROM clause
	* JOIN clause
	* WITH clause
	* SELECT clause
	
	A name => schema pair must be stored for each item in the above list.
	The name must be the alias name for each item or the property chain
	if the alias does not exist. After a name is stored, it can be used 
	as an explicit root entity class.

3. Initializing tables
	searching for all atomic properties whose table appears in the SQL query. 
	
	A table must appear in the query in the following cases:
	* at least one atomic property appears in the select clause that is
		mapped to the table
	* at 
	
	
	
	
	
	
	
	
	
	
	
	
	should be selected. An atomic
	property should be selected in the following cases:
	* all atomic properties of the select list items without projection should
		be selected
	* if there is a projected property in the select list, then for all 
		items in the projection:
		* if the item is an atomic property, then it should be selected
		* otherwise the join columns should be selected (?)
	* if a property appears in the WITH clause, then all of it's atomic
		properties should be selected if it is a component, otherwise
		it must be selected

