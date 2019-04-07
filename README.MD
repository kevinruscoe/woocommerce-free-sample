# What is this?

Basically, it adds a button after the "add to cart" button that:

- Creates a (temporary) sample product, based on the current product
- Adds it to the cart
- Hides it from the admin-grid (to prevent noise)

The customer is limited to X amount of free samples (see `functions.sample.php@only_free_up_to()`).
If they add more than X they are charged `functions.sample.php@sample_charge_if_needed()`

Another couple of things:

- If a customer removes/sets quantity to zero, it'll delete the sample from WordPress.
- If an order is marked as "complete" or "cancelled", it'll also delete the samples from WordPress.
- If a customer adds the same sample twice, they'll be told they're an idiot.