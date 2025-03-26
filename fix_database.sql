-- Drop existing foreign key constraint
ALTER TABLE products DROP FOREIGN KEY products_ibfk_1;

-- Update the foreign key to reference sellerdetails instead of seller
ALTER TABLE products
ADD CONSTRAINT products_ibfk_1
FOREIGN KEY (seller_id) REFERENCES sellerdetails(seller_id)
ON DELETE CASCADE
ON UPDATE CASCADE;
