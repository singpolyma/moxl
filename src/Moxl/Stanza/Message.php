<?php

namespace Moxl\Stanza;

use Movim\Session;

class Message
{
    static function maker(
        $to,
        $content = false,
        $html = false,
        $type = 'chat',
        $chatstates = false,
        $receipts = false,
        $id = false,
        $replace = false,
        $file = false)
    {
        $session = Session::start();

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $root = $dom->createElementNS('jabber:client', 'message');
        $dom->appendChild($root);
        $root->setAttribute('to', str_replace(' ', '\40', $to));
        $root->setAttribute('type', $type);

        if($id != false) {
            $root->setAttribute('id', $id);
        } else {
            $root->setAttribute('id', $session->get('id'));
        }

        if($content != false) {
            $body = $dom->createElement('body', $content);
            $root->appendChild($body);
        }

        if($replace != false) {
            $rep = $dom->createElementNS('urn:xmpp:message-correct:0', 'replace');
            $rep->setAttribute('id', $replace);
            $root->appendChild($rep);
        }

        if($html != false) {
            $xhtml = $dom->createElementNS('http://jabber.org/protocol/xhtml-im', 'html');
            $body = $dom->createElementNS('http://www.w3.org/1999/xhtml', 'body');

            $dom2 = new \DOMDocument('1.0', 'UTF-8');
            $dom2->loadXml('<root>'.$html.'</root>');
            $bar = $dom2->documentElement->firstChild; // we want to import the bar tree
            $body->appendChild($dom->importNode($bar, TRUE));

            $xhtml->appendChild($body);
            $root->appendChild($xhtml);
        }

        if($chatstates != false) {
            $chatstate = $dom->createElementNS('http://jabber.org/protocol/chatstates', $chatstates);
            $root->appendChild($chatstate);
        }

        if($receipts != false) {
            if($receipts == 'request') {
                $request = $dom->createElement('request');
            } else {
                $request = $dom->createElement('received');
                $request->setAttribute('id', $receipts);
            }
            $request->setAttribute('xmlns', 'urn:xmpp:receipts');
            $root->appendChild($request);
        }

        if($file != false) {
            $reference = $dom->createElement('reference');
            $reference->setAttribute('xmlns', 'urn:xmpp:reference:0');
            $reference->setAttribute('type', 'data');
            $root->appendChild($reference);

            $media = $dom->createElement('media-sharing');
            $media->setAttribute('xmlns', 'urn:xmpp:sims:1');
            $reference->appendChild($media);

            $filen = $dom->createElement('file');
            $filen->setAttribute('xmlns', 'urn:xmpp:jingle:apps:file-transfer:4');
            $media->appendChild($filen);

            $filen->appendChild($dom->createElement('media-type', $file->type));
            $filen->appendChild($dom->createElement('name', $file->name));
            $filen->appendChild($dom->createElement('size', $file->size));

            $sources = $dom->createElement('sources');
            $media->appendChild($sources);

            $reference = $dom->createElement('reference');
            $reference->setAttribute('xmlns', 'urn:xmpp:reference:0');
            $reference->setAttribute('type', 'data');
            $reference->setAttribute('uri', $file->uri);

            $sources->appendChild($reference);
        }

        \Moxl\API::request($dom->saveXML($dom->documentElement));
    }

    static function message($to, $content, $html = false, $id = false, $replace = false, $file = false)
    {
        self::maker($to, $content, $html, 'chat', 'active', 'request', $id, $replace, $file);
    }

    static function composing($to)
    {
        self::maker($to, false, false, 'chat', 'composing');
    }

    static function paused($to)
    {
        self::maker($to, false, false, 'chat', 'paused');
    }

    static function receipt($to, $id)
    {
        self::maker($to, false, false, 'chat', false, $id);
    }
}
