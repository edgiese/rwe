--:create:C creates a new 
-->peachid:4,description:4
INSERT INTO pa_upc (peachid,description) VALUES (:peachid,:description)

--:createbarcode:C adds bar code
-->id:4,barcode:4
INSERT INTO pa_barcodes (id,barcode) VALUES (:id,:barcode)
