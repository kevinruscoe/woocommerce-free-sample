# What is this?

Basically, it adds a button after the "add to cart" button that:

- Creates a (temporary) sample product, based on the current product
- Adds it to the cart
- Hides it from the admin-grid (to prevent noise)

The customer is limited to X amount of samples (see `functions.sample.php@allowed_max_samples()`).

Another couple of things:

- If a customer removes/sets quantity to zero, it'll delete the sample from WordPress.
- If an order is marked as "complete" or "cancelled", it'll also delete the samples from WordPress.
- If a customer adds the same sample twice, they'll be told they're an idiot.