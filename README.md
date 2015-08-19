The IHTFP Hack Gallery website was written in 1996, in an era when XML and bash 
generation scripts were all the rage. Nearly two decades later, the site is 
clunky to update. That's a problem we need to address, but in the interim, I'm 
trying to make it easier for staff to keep uploading hack information to the 
site.

There are two main pages:

- **index.php** is a fairly straightforward form with the details necessary for 
the hack page template XML. It generates that XML file when you submit and gives 
you a list of terminal commands necessary to move, edit, and launch your hack 
submission on the Gallery.

- **photos.php** is a clunky photo uploader, which is intended to make it easier 
to get photos onto the site as well. It lets you know when you've uploaded 
something successfully and provides the relevant <photo> XHTML tag for insertion 
into your hack submission writeup.

I hope to clean these up more and eventually make the photos page part of the 
main one, so you can upload photos and immediately use them. I also hope to work 
out the aklog problem, so the PHP script can do more of the heavy lifting behind 
the scenes first. Maybe I should write and include a bash script?

This is released under the MIT License, not that I really ever expect anyone to 
want to use my messy code.
