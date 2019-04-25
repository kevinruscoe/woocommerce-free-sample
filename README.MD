[![Codacy Badge](https://api.codacy.com/project/badge/Grade/35a97946d4a9413d98e271ebf634824c)](https://www.codacy.com/app/kevdotbadger/woocommerce-request-sample-product?utm_source=github.com&amp;utm_medium=referral&amp;utm_content=kevinruscoe/woocommerce-request-sample-product&amp;utm_campaign=Badge_Grade)

# What is this?

Basically, it adds a button after the "add to cart" button that:

- Creates a (temporary) sample product, based on the current product
- Adds it to the cart
- Hides it from the admin-grid (to prevent noise)

Products:

- Have an option to toggle if a sample is available

You can also:

- Allow free samples upto an amount (i.e. you're allowed 5 samples for free)
- Set a flat-rate fee for none-free samples (i.e. It's £5 for more than 5 samples)

Another few things:

- If a customer removes/sets quantity to zero, it'll delete the sample from WordPress.
- If an order is marked as "complete" or "cancelled", it'll also delete the samples from WordPress.
- If a customer adds the same sample twice, they'll be told they're an idiot.

## Need this tweaking?

Drop me an email hello@kevinruscoe.me and we'll sort something out.
