<?php

/*

Plugin Name: Load More ajax for Books.
Description: Plugin for manages templates and Frontend Shortcode.
Author: SK
Plugin Url: http://localhost.com/new-book
*/


class NEW_BOOK
{

    function __construct()
    {
        add_action('wp_enqueue_scripts', [$this, 'addScripts']);
        add_action('wp_ajax_newBook', array($this, 'newBook'));
        add_action('wp_ajax_nopriv_newBook', array($this, 'newBook'));
        add_shortcode('book_form', array($this, 'bookFormShortcode'));
        add_shortcode('list_book', array($this, 'bookListShortcode'));
        add_action('wp_ajax_load_more_books', array($this, 'load_more_books_callback'));
        add_action('wp_ajax_nopriv_load_more_books', array($this, 'load_more_books_callback'));
    }


public function addScripts()
{
    wp_enqueue_style(
        'bootstrap-css',
        'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css',
        [],
        '5.3.3'
    );

    wp_enqueue_script(
        'book-js',
        plugins_url('assets/book.js', __FILE__),
        ['jquery'],
        '1.0',
        true
    );

    wp_localize_script('book-js', 'my_ajax_object', [
        'ajax_url' => admin_url('admin-ajax.php'),
    ]);

    wp_enqueue_script(
        'sweetalert',
        'https://cdn.jsdelivr.net/npm/sweetalert2@11',
        [],
        null,
        true
    );
}


  public function newBook()
{
    if (!isset($_POST['name'])) {
        wp_send_json_error(['message' => 'Invalid request']);
    }

    $name        = sanitize_text_field($_POST['name']);
    $author_name = sanitize_text_field($_POST['author_name']);
    $book_price  = sanitize_text_field($_POST['book_price']);
    $image_url   = '';

    if (!empty($_FILES['image_url']['name'])) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $attachment_id = media_handle_upload('image_url', 0);

        if (is_wp_error($attachment_id)) {
            wp_send_json_error(['message' => 'Image upload failed']);
        }

        $image_url = wp_get_attachment_url($attachment_id);
    }

    global $wpdb;
    $wpdb->insert(
        $wpdb->prefix . 'book_table',
        [
            'name'        => $name,
            'author_name' => $author_name,
            'book_price'  => $book_price,
            'image_url'   => $image_url,
        ]
    );

    wp_send_json_success(['message' => 'Book added successfully']);
}



public function bookFormShortcode()
{
    ob_start();
    ?>

    <div class="container my-5">
        <h3 class="mb-4">Add a New Book</h3>

        <form id="frm-add-book" enctype="multipart/form-data">
            <div class="mb-3">
                <label class="form-label">Book Name</label>
                <input type="text" class="form-control" name="name" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Author Name</label>
                <input type="text" class="form-control" name="author_name" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Price</label>
                <input type="number" class="form-control" name="book_price" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Book Image</label>
                <input type="file" class="form-control" name="image_url" accept="image/*">
            </div>

            <button type="submit" class="btn btn-primary">Add Book</button>
        </form>
    </div>

    <?php
    return ob_get_clean();
}




 public function bookListShortcode()
{
    ob_start();
    global $wpdb;
    $table = $wpdb->prefix.'book_table';

    $books = $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC LIMIT 3");
    ?>

    <div class="container my-5">
        <div id="books-container">
            <div class="row">
                <?php foreach ($books as $book) : ?>
                    <div class="col-md-4 mb-4">
                        <div class="card shadow h-100">
                            <?php if ($book->image_url) : ?>
                                <img src="<?php echo esc_url($book->image_url); ?>" class="card-img-top">
                            <?php endif; ?>
                            <div class="card-body">
                                <h5><?php echo esc_html($book->name); ?></h5>
                                <p>
                                    <strong>Author:</strong> <?php echo esc_html($book->author_name); ?><br>
                                    <strong>Price:</strong> <?php echo esc_html($book->book_price); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <button id="load-more-button" class="btn btn-primary mt-3">Load More</button>
    </div>

    <?php
    return ob_get_clean();
}



  public function load_more_books_callback()
{
    global $wpdb;
    $table = $wpdb->prefix . 'book_table';

    $offset = intval($_POST['offset']);
    $count  = intval($_POST['count']);

    $books = $wpdb->get_results(
        $wpdb->prepare("SELECT * FROM $table ORDER BY id DESC LIMIT %d OFFSET %d", $count, $offset)
    );

    ob_start();

    foreach ($books as $book) :
    ?>
        <div class="col-md-4 mb-4">
            <div class="card shadow h-100">
                <?php if ($book->image_url) : ?>
                    <img src="<?php echo esc_url($book->image_url); ?>" class="card-img-top">
                <?php endif; ?>
                <div class="card-body">
                    <h5><?php echo esc_html($book->name); ?></h5>
                    <p>
                        <strong>Author:</strong> <?php echo esc_html($book->author_name); ?><br>
                        <strong>Price:</strong> <?php echo esc_html($book->book_price); ?>
                    </p>
                </div>
            </div>
        </div>
    <?php
    endforeach;

    wp_send_json_success([
        'html' => ob_get_clean(),
        'has_more_books' => count($books) === $count,
    ]);
}

}

new NEW_BOOK();
