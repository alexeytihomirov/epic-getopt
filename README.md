# epic-getopt
getopt with arguments


        $opt = new Getopt();
        $opt->setOption('f', '-f', Getopt::VALUE_REQUIRED);
        $opt->setOption('p', '-p', Getopt::VALUE_REQUIRED);
        $opt->setOption('h', '-h', Getopt::VALUE_NOT_ACCEPT);
        $opt->setOption('a', '-a', Getopt::VALUE_NOT_ACCEPT);
        $opt->setOption('b', '-b', Getopt::VALUE_NOT_ACCEPT);
        $opt->setOption('c', '-c', Getopt::VALUE_NOT_ACCEPT);


        $customOptions = $opt();
        var_dump($customOptions, $opt->arguments);
