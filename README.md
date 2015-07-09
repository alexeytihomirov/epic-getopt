# epic-getopt
getopt with arguments


        $opt = (new Getopt())
            ->setOption('f', '-f', Getopt::VALUE_REQUIRED)
            ->setOption('p', '-p', Getopt::VALUE_REQUIRED)
            ->setOption('h', '-h', Getopt::VALUE_NOT_ACCEPT)
            ->setOption('a', '-a', Getopt::VALUE_NOT_ACCEPT)
            ->setOption('b', '-b', Getopt::VALUE_NOT_ACCEPT)
            ->setOption('c', '-c', Getopt::VALUE_NOT_ACCEPT);


        $customOptions = $opt();
        var_dump($customOptions, $opt->arguments);
