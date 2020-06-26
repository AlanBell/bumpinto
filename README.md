Privacy first contact tracking

This is a web based contact tracker, which may be useful for pandemic situations. The tool will open a camera and show a QR code, one phone can scan the QR code on another and an interaction is created between the two people
who bumped into each other. This common code is stored on both devices in an indexeddb database. It isn't sent to the server if they both stay healthy.
Should one of them develop symptoms and need to alert their contacts they can then scan a diagnosis code, which triggers an upload to the server of the random interaction IDs. Other phones can look at the uploaded interactions
to see if they share any. This leaves minimal data on the server and very little scope for the server operator to do privacy invasive surveilance.

As a feature to help the users of the system they can optionally exchange contact details as they bump. This never gets sent to the server, and can be removed if users want - it will still retain the anonymous interactionid.



Uses some external libraries

Generating good UUIDs
https://github.com/uuidjs/uuid

creating QR codes
http://davidshimjs.github.io/qrcodejs/

reading QR codes
https://github.com/cozmo/jsQR

