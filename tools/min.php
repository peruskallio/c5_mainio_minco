<?php defined('C5_EXECUTE') or die(_("Access Denied."));

Loader::library("minco_file_writer", "mainio_minco");
MincoFileWriter::respondMinifyRequest();
