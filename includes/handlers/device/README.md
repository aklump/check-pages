# Device

Provides the ability to explicitly set some device characteristics for a given test.

* Device width
* Device height
* Device pixel ratio

## Pixel Ratio

A double-precision floating-point value indicating the ratio of the display's resolution in physical pixels to the resolution in CSS pixels. A value of 1 indicates a classic 96 DPI (76 DPI on some platforms) display, while a value of 2 is expected for HiDPI/Retina displays. Other values may be returned as well in the case of unusually low resolution displays or, more often, when a screen has a higher pixel depth than double the standard resolution of 96 or 76 DPI. [Read more...](https://developer.mozilla.org/en-US/docs/Web/API/Window/devicePixelRatio)

