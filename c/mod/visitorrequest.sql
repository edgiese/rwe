--crud queries for visitorrequest module

--:create:C creates a new action from a description
-->type:4,data:4
INSERT INTO visitorrequest_main (actiondesc,actiondata) VALUES (:type,:data)

--:countbyactiondesc:R return count of records for a given actiondesc
-->type:4
--<count:2|1
SELECT COUNT(*) FROM visitorrequest_main WHERE actiondesc=:type

--:count:R return count of records for a given actiondesc
--<count:2|1
SELECT COUNT(*) FROM visitorrequest_main

--:requestbyactiondesc:R gets request data for a particular action type
-->type:4
--<id:4,type:4,timestamp:2,data:4
SELECT idrequest,actiondesc,UNIX_TIMESTAMP(requesttime),actiondata FROM visitorrequest_main WHERE actiondesc=:type ORDER BY requesttime DESC

--:allrequests:R gets all request data
--<id:4,type:4,timestamp:2,data:4
SELECT idrequest,actiondesc,UNIX_TIMESTAMP(requesttime),actiondata FROM visitorrequest_main ORDER BY requesttime DESC

--:update:D updates data (only) for the id of an action
-->id:4,data:4
UPDATE visitorrequest_main SET actiondata=:data WHERE idrequest=:id

--:delete:D deletes a visitor request record
-->id:4
DELETE FROM visitorrequest_main WHERE idrequest=:id

