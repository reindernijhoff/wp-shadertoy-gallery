# Wordpress Shadertoy Gallery Plugin

This Wordpress plugin enables a shortcode that can be used to add a gallery with [Shadertoy](https://www.shadertoy.com) shaders to your Worpress site. The content of the gallery is based on a _query_ attribute of the shortcode. The content of the gallery will update automatically.

You can find a live demo of this plugin [here](https://reindernijhoff.net/shadertoy/).

Note:
- This is the first Wordpress plugin I have ever made. 
- You *need* a Shadertoy API key for this plugin. You can request a key [here](https://www.shadertoy.com/howto).
- The plugin can only find and display shaders that are published on Shadertoy using the _API+Public_ option.

I don't want to DOS Shadertoy and I want a fast plugin. Therefore, a lot of results are cached: the result of a query will be cached for (at least) one day; the title of an individual shader for (at least) 14 days. Expect a (extremely) long page load the first time you open the Gallery. A second time (most) will be cached and you should have a fast response.

## Installation

Copy the _shadertoy_ directory into _wp-content/plugins_ and activate the plugin in the Admin.

## Basic usage

Add a _shadertoy-list_ shortcode to your post or page. If you want to create a gallery with all shaders that match the query 'raymarch', you use:

```
[shadertoy-list query="raymarch" key="YOUR_SHADERTOY_API_KEY_HERE"]
```

## Optional attributes

You can use the following (optional) attributes:

- *query* - The query term.
- *key* - Your Shadertoy API key.
- *username* (optional) - Only shaders created by the user with this username will be visible.
- *columns* (optional, default = 2) - Number of columns of the gallery. Values 1,2,3 and 4 are supported.
- *sort* (optional, default = newest) - Order of the the shaders. Supported values: "name", "love", "popular", "newest", "hot".
