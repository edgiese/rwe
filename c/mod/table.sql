--:nextInsertRowId:R finds the next available id for a row
-->keycol:2
--<count:4|1
SELECT COUNT(*) FROM table_strings WHERE col=:keycol

--:insertint:C inserts an integer value
-->row:2,colid:2,value:2
INSERT INTO table_ints (rownum,col,celldata) VALUES (:row,:colid,:value)

--:insertstring:C inserts a text value
-->row:2,colid:2,value:4
INSERT INTO table_strings (rownum,col,celldata) VALUES (:row,:colid,:value)

--:infofromfilename:R gets information about a table from the file name that created it
-->filename:4
--<id:2,lastedit:2,title:4,description:4|1				
SELECT idtable,lastedit,title,description FROM table_def WHERE filename=:filename

--:deletetabledata:D deletes all data for a table
-->id:2
DELETE table_cols,table_ints,table_strings FROM table_cols LEFT JOIN table_ints ON table_cols.idcol=table_ints.col LEFT JOIN table_strings ON table_cols.idcol=table_strings.col WHERE table_cols.idtable=:id

--:insertstub:C inserts a stub entry for a table (to get id)
-->filename:4
INSERT INTO table_def (title,description,filename,lastedit) VALUES ('','',:filename,0)

--:coldef:C inserts a new column definition for a table
-->id:2,tag:4,coltype:4,heading:4,description:4,defaultval:4
INSERT INTO table_cols (idtable,name,coltype,heading,description,defaultval) VALUES (:id,:tag,:coltype,:heading,:description,:defaultval)

--:updatenames:D updates names in a table description row
-->id:2,title:4,description:4,lastedit:2
UPDATE table_def SET title=:title,description=:description,lastedit=:lastedit WHERE idtable=:id 

--:tableinfo:R gets title and description of a table, given an id
-->id:2
--<title:4,description:4|1				
SELECT title,description FROM table_def WHERE idtable=:id

--:colinfo:R gets all column information for a table, given an id
-->id:2
--<name:4,coltype:4,heading:4,description:4,defaultval:4,colid:2
SELECT name,coltype,heading,description,defaultval,idcol FROM table_cols WHERE idtable=:id

--:strings:R gets all string values for a table
-->id:2
--<rownum:2,col:2,celldata:4
SELECT rownum,col,celldata FROM table_strings INNER JOIN table_cols ON col=idcol WHERE idtable=:id

--:ints:R gets all integers values for a table
-->id:2
--<rownum:2,col:2,celldata:2
SELECT rownum,col,celldata FROM table_ints INNER JOIN table_cols ON col=idcol WHERE idtable=:id
