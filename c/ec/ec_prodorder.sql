--insert('ec/ec_prodorder::insert',$cust->getEmail(),$name,$zip,serialize($cust),serialize($this));
--:insert:C inserts a new record
-->email:4,name:4,zip:4,custobj:4,orderobj:4
INSERT INTO prodorder_main (email,via,tracking,name,zip,custobj,orderobj) VALUES (:email,'(not shipped)','(none)',:name,:zip,:custobj,:orderobj)

--getRows('ec/ec_prodorder::allorders',-1);
--:allorders:R gets summary data about all orders
--<id:2,timestamp:4,email:4,via:4,tracking:4,name:4,zip:4
SELECT id,UNIX_TIMESTAMP(placed),email,via,tracking,name,zip FROM prodorder_main ORDER BY placed DESC

--getRows('ec/ec_prodorder::info',1,(int)$idorder);
--:info:R reads info values for an order
-->id:2
--<custobj:4,orderobj:4,timestamp:4,email:4,via:4,tracking:4,name:4,zip:4|1
SELECT custobj,orderobj,UNIX_TIMESTAMP(placed),email,via,tracking,name,zip FROM prodorder_main WHERE id=:id 

--act('ec/ec_prodorder::updateorderobj',$id,serialize($this));
--:updateorderobj:D updates the order object for an id
-->id:2,orderobj:4
UPDATE prodorder_main SET orderobj=:orderobj WHERE id=:id

--act('ec/ec_prodorder::updateshipping',$id,$via,$tracking);
--:updateshipping:D updates shipping data for an id
-->id:2,via:4,tracking:4
UPDATE prodorder_main SET via=:via,tracking=:tracking WHERE id=:id

--:firstunproc:R finds oldest order that has not shipped
--<id:2|1
SELECT id FROM prodorder_main WHERE tracking='(none)' ORDER BY placed ASC LIMIT 0,1

--:lastunproc:R finds newest order that has not shipped
--<id:2|1
SELECT id FROM prodorder_main WHERE tracking='(none)' ORDER BY placed DESC LIMIT 0,1

--:nextunproc:R finds next newest order that has not shipped
-->id:2
--<id:2|1
SELECT id FROM prodorder_main WHERE tracking='(none)' AND id > :id ORDER BY placed ASC LIMIT 0,1

--:prevunproc:R finds next oldest order that has not shipped
-->id:2
--<id:2|1
SELECT id FROM prodorder_main WHERE tracking='(none)' AND id < :id ORDER BY placed DESC LIMIT 0,1
