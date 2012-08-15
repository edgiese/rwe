--crud queries for main product module

--:lookupinvid:R find an id for an invid
-->invid:4
--<id:2|1
SELECT id FROM prod_main WHERE invid=:invid

--:allIds:R return ids for all entries in item list
--<id:2
SELECT id FROM prod_main

--:idfrombarcode:R return id for a particular barcode
-->barcode:4
--<id:2|1
SELECT id FROM prod_barcodes WHERE barcode=:barcode

--:allActiveIds:R return ids for all active (non-held) entries in item list
--<id:2
SELECT id FROM prod_main WHERE prodhold=0

--:insert:C adds a new id to the product table
-->invid:4,invdesc:4,prodhold:2,notes:4,baseprice:2,firstsold:4,lastsold:4,onhand:2,minqty:2,discontinued:1
INSERT INTO prod_main (invid,invdesc,prodhold,notes,baseprice,firstsold,lastsold,onhand,minqty,discontinued) VALUES (:invid,:invdesc,:prodhold,:notes,:baseprice,:firstsold,:lastsold,:onhand,:minqty,:discontinued)

--:getinfo:R gets info about an id
-->id:4
--<invid:4,invdesc:4,prodhold:2,notes:4,baseprice:2,firstsold:4,lastsold:4,onhand:2,minqty:2,discontinued:1|1
SELECT invid,invdesc,prodhold,notes,baseprice,firstsold,lastsold,onhand,minqty,discontinued FROM prod_main WHERE id=:id

--:getbarcodes:R get barcode information for an item
-->id:4
--<barcode:4
SELECT barcode FROM prod_barcodes WHERE id=:id

--:deletebarcodes:D delete all barcodes for an item
-->id:4
DELETE FROM prod_barcodes WHERE id=:id

--:insertbarcode:C create a new bar code entry for an item
-->id:4,barcode:4
INSERT INTO prod_barcodes (id,barcode) VALUES (:id,:barcode)

--:setinfo:D updates info for a product id
-->id:4,invid:4,invdesc:4,prodhold:2,notes:4,baseprice:2,firstsold:4,lastsold:4,onhand:2,minqty:2,discontinued:1
UPDATE prod_main SET invid=:invid,invdesc=:invdesc,prodhold=:prodhold,notes=:notes,baseprice=:baseprice,firstsold=:firstsold,lastsold=:lastsold,onhand=:onhand,minqty=:minqty,discontinued=:discontinued WHERE id=:id

--:numholds:R returns number of items on hold
--<num:2|1
SELECT count(id) FROM prod_main WHERE prodhold <> 0

--:numnotes:R returns number of items with notes
--<num:2|1
SELECT count(id) FROM prod_main WHERE notes IS NOT NULL AND notes <> ''

--:prevnohold:R returns id of nav item
-->id:4
--<id:4
SELECT id FROM prod_main WHERE id < :id AND prodhold=0 ORDER BY id DESC limit 0,1

--:prevhold:R returns id of nav item
-->id:4
--<id:4
SELECT id FROM prod_main WHERE id < :id AND prodhold=1 ORDER BY id DESC limit 0,1

--:prevnote:R returns id of nav item
-->id:4
--<id:4
SELECT id FROM prod_main WHERE id < :id AND notes IS NOT NULL AND notes <> '' ORDER BY id DESC limit 0,1

--:maxid:R gets maximum value of id
--<id:4
SELECT max(id) FROM prod_main 
 
--:nextnohold:R returns id of nav item
-->id:4
--<id:4
SELECT id FROM prod_main WHERE id > :id AND prodhold=0 ORDER BY id limit 0,1

--:nexthold:R returns id of nav item
-->id:4
--<id:4
SELECT id FROM prod_main WHERE id > :id AND prodhold=1 ORDER BY id limit 0,1

--:nextnote:R returns id of nav item
-->id:4
--<id:4
SELECT id FROM prod_main WHERE id > :id AND notes IS NOT NULL AND notes <> '' ORDER BY id limit 0,1


--:syncprepcheck:R returns count of rows in prod_main_save: used to see if it needs to be done
--<count:2|1
SELECT COUNT(id) FROM prod_main_save
 
--:syncprep1:D backs up tables
TRUNCATE prod_main_save
--:syncprep2:D backs up tables
INSERT INTO prod_main_save SELECT * FROM prod_main
--:syncprep3:D backs up tables
TRUNCATE prod_barcodes_save
--:syncprep4:D backs up tables
INSERT INTO prod_barcodes_save SELECT * FROM prod_barcodes
--:syncprep5:D backs up tables
TRUNCATE prod_taxrates_save
--:syncprep6:D backs up tables
INSERT INTO prod_taxrates_save SELECT * FROM prod_taxrates

--:markalldis:D marks all products as discontinued as prep for sync
UPDATE prod_main SET discontinued=1

--:countdiscontinued:R counts all discontinued items
--<count:2|1
SELECT COUNT(id) FROM prod_main WHERE discontinued=1

--:synccommit1:D allows backups next time
TRUNCATE prod_main_save
--:synccommit2:D allows backups next time
TRUNCATE prod_barcodes_save
--:synccommit3:D allows backups next time
TRUNCATE prod_taxrates_save

--:syncrollback1:D backs up tables
TRUNCATE prod_main
--:syncrollback2:D backs up tables
INSERT INTO prod_main SELECT * FROM prod_main_save
--:syncrollback3:D backs up tables
TRUNCATE prod_barcodes
--:syncrollback4:D backs up tables
INSERT INTO prod_barcodes SELECT * FROM prod_barcodes_save
--:syncrollback5:D backs up tables
TRUNCATE prod_taxrates
--:syncrollback6:D backs up tables
INSERT INTO prod_taxrates SELECT * FROM prod_taxrates_save


--:onholditems:R gets items on hold prioritized
--<id:2
SELECT id FROM prod_main WHERE prodhold=1 ORDER BY lastsold DESC,baseprice ASC

--:onholditemsbyinvdesc:R gets items on hold prioritized
--<id:2
SELECT id FROM prod_main WHERE prodhold=1 ORDER BY invdesc

--:addedbysync:R gets items added by sync
--<id:2
SELECT id FROM prod_main WHERE prodhold=1 AND notes LIKE 'Added by Sync%' ORDER BY invdesc

--:addtax:D adds a tax rate for an item and state
-->id:2,state:4,rate:2
INSERT INTO prod_taxrates (prodid,statecode,rate) VALUES (:id,:state,:rate)

--:cleartax:D removes tax for an item and state
-->id:2,state:4
DELETE FROM prod_taxrates WHERE prodid=:id AND statecode=:state

--:gettax:R finds a tax rate
-->id:2,state:4
--<rate:2
SELECT rate FROM prod_taxrates WHERE prodid=:id AND statecode=:state

--:autoholdzeroprice:D automatically puts zero price items on hold
UPDATE prod_main SET prodhold=1 WHERE baseprice=0
