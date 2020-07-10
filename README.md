# Check Pages

Lorem ipsum dolor sit amet, consectetur adipiscing elit. Praesent ac blandit risus. Mauris tempor a lacus a placerat. Vivamus viverra dapibus metus non finibus. Nulla ultricies est nulla, eget efficitur nibh viverra non. Sed sed est viverra nunc malesuada venenatis vitae at tellus. Suspendisse potenti. Morbi non blandit elit, sit amet consectetur mi.

## Install Globally Using Composer

To be able to use `check` from any directory in your CLI you may want to install this globally.

    composer global require aklump/check_pages

Make sure you have the composer bin dir in your `PATH`. The default value is _~/.composer/vendor/bin_, but you can check the value that you need to use by running `composer global config bin-dir --absolute`.
    
To check this you must open _~/.bash_profile_ (or _~/.bashrc_); you're looking for a line that looks like the following, if you can't find, you'll need to add it.
                                 
    export PATH=~/.composer/vendor/bin:$PATH

## Usage

    $ check
    
## Contributing

If you find this project useful... please consider [making a donation](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=4E5KZHDQCEUV8&item_name=Gratitude%20for%20aklump%2Fcheck-pages).




## Installation

1. `composer install`


## Limitations

* This does not run Javascript, so DOM selections will not work if they require that JS run.
