<?php

function go_git()
{
	global $go_git;

	if ( ! isset( $go_git ) )
	{
		$go_git = new GO_Git;
	}//end if

	return $go_git;
}//end go_git
