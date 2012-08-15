--Hill country needs council resource directory
--comments are not arbitrary

--insert("mod/proj/hcncrg::create",$title,$description,$keywords,$notes);
--:create:C creates new record
-->title:4,description:4,keywords:4,notes:4
INSERT INTO hcncrg_main (title,description,keywords,notes) VALUES (:title,:description,:keywords,:notes)

--insert("mod/proj/hcncrg::addhistory",$id,$qqs->getUser(),'Created');
--:addhistory:C creates a new history record
-->id:4,userid:2,changes:4
INSERT INTO hcncrg_history (id,userid,changes) VALUES (:id,:userid,:changes)

--getRows("mod/proj/hcncrg::keywords",-1);
--:keywords:R gets keywords for all ids
--<keywords:4
SELECT keywords FROM hcncrg_main

--getRows("mod/proj/hcncrg::allids",-1);
--:allids:R gets all ids in the resource guide
--<id:4,keywords:4,title:4,desc:4
SELECT id,keywords,title,description FROM hcncrg_main ORDER BY title

--getRows('mod/proj/hcncrg::info',1,$id)
--:info:R gets information about an id
-->id:4
--<title:4,keywords:4,desc:4,notes:4
SELECT title,keywords,description,notes FROM hcncrg_main WHERE id=:id

--getRows('mod/proj/hcncrg::history',-1,$id);
--:history:R gets all history data for an id
-->id:4
--<userid:4,timestamp:2,changes:4
SELECT userid,UNIX_TIMESTAMP(edittime),changes FROM hcncrg_history WHERE id=:id ORDER BY edittime DESC

--("mod/proj/hcncrg::update",$id,$title,$keywords,$desc,$notes)
--:update:D updates an entry
-->id:4,title:4,keywords:4,desc:4,notes:4
UPDATE hcncrg_main SET title=:title,keywords=:keywords,description=:desc,notes=:notes WHERE id=:id

--$qqc->act("mod/proj/hcncrg::delete",$shortname);
--:delete:D deletes an entry
-->id:4
DELETE FROM hcncrg_main,hcncrg_history USING hcncrg_main INNER JOIN hcncrg_history ON hcncrg_main.id=hcncrg_history.id WHERE hcncrg_main.id=:id
