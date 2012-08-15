--Hill country needs council resource directory
--comments are not arbitrary


--$qqc->insert("mod/proj/hcncrg::create",$shortname);
--:create
--:shortname:4,text:4
INSERT INTO hcncrg_main (shortname,keywords,text) VALUES (:shortname,:keywords,:text)


--return $qqc->getRows("mod/proj/hcncrg::keywords",1,$shortname);
--:keywords
-->shortname:4
--<keywords:4|1
SELECT keywords FROM hcncrg_main WHERE shortname=:shortname

--$rows=$qqc->getRows($bSortByFilter ? 'mod/proj/hcncrg::namesbykeyword' : 'mod/proj/hcncrg::namesbyname',-1);
--:namesbykeyword
--<shortname:4,keywords:4
SELECT shortname,keywords FROM hcncrg_main ORDER BY keywords

--:namesbyname
--<shortname:4,keywords:4
SELECT shortname,keywords FROM hcncrg_main ORDER BY shortname


--if (False === ($text=$qqc->getRows("mod/proj/hcncrg::textfromname",1,$shortname)))
--:textfromname
-->shortname:4
--<text:4
SELECT text FROM hcncrg_main WHERE shortname=:shortname

--$qqc->act("mod/proj/hcncrg::settext",$shortname,$creole);
--:settext
-->shortname:4,text:4
UPDATE hcncrg_main SET text=:text WHERE shortname=:shortname

--:setkeywords
-->shortname:4,keywords:4
UPDATE hcncrg_main SET keywords=:keywords WHERE shortname=:shortname


--$qqc->act("mod/proj/hcncrg::delete",$shortname);
--:delete
-->shortname:4
DELETE FROM hcncrg_main WHERE shortname=:shortname
