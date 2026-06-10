from woork_agent import __version__


def test_version() -> None:
    assert __version__ == "1.0.0"


def test_agent_entry_preflight() -> None:
    import agent_entry

    assert agent_entry._run_preflight() == 0
