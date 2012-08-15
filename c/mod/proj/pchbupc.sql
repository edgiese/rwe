--Peach Basket barcode lookup untilities
--comments are not arbitrary

--:nextupc:R gets the next upc in the database
-->lastbarcode:4
--<id:4,barcode:4
--SELECT id,barcode FROM prod_barcodes  WHERE barcode > :lastbarcode ORDER BY barcode LIMIT 0,1
SELECT prod_barcodes.id,prod_barcodes.barcode FROM prod_barcodes LEFT JOIN prod_info ON prod_barcodes.id=prod_info.id LEFT JOIN pa_sites ON prod_barcodes.id=pa_sites.id WHERE prod_info.id IS NULL AND pa_sites.id IS NULL AND prod_barcodes.barcode > :lastbarcode ORDER BY barcode LIMIT 0,1

--:newsite:C adds new site info for a barcode
-->id:4,barcode:4,site:4,query:4
INSERT INTO pa_sites (id,barcode,site,query) VALUES (:id,:barcode,:site,:query)

--:nextsiterow:R reads the next site row that hasn't been processed
--<id:4,barcode:4,site:4,query:4|1
SELECT id,barcode,site,query FROM pa_sites WHERE processed=0 LIMIT 0,1

--:markrowdone:D marks a site row as done
-->id:4,site:4
UPDATE pa_sites SET processed=1 WHERE id=:id AND site=:site

--:newattrib:C adds a new attribute candidate for a product id
-->id:4,datatype:4,datafunc:4,source:4,datatext:4
INSERT INTO prod_attribtext(id,datatype,datafunc,source,datatext) VALUES (:id,:datatype,:datafunc,:source,:datatext)

