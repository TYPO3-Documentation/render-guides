import React, { useEffect, useState } from 'react';
import SearchModal from './SearchModal';

const GlobalSearch = ({ displayInput = false }) => {
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [searchQuery, setSearchQuery] = useState('');

    useEffect(() => {
        const url = new URL(window.location.href);
        const queryParam = url.searchParams.get('q');
        if (queryParam) {
            setSearchQuery(queryParam);
        }
    }, []);

    useEffect(() => {
        if (displayInput) return; // only the header instance listens, or the search page opens two modals
        const onKeyDown = (e) => {
            if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
                e.preventDefault(); // browsers use Ctrl+K to focus the address bar
                setIsModalOpen(true);
            }
        };
        document.addEventListener('keydown', onKeyDown);
        return () => document.removeEventListener('keydown', onKeyDown);
    }, [displayInput]);

    const handleButtonClick = (e) => {
        e.preventDefault();
        setIsModalOpen(true);
    };

    return (
        <>
            {isModalOpen && <SearchModal
                isOpen={isModalOpen}
                onClose={() => setIsModalOpen(false)}
            />}
            {displayInput ? <div class="input-group mb-3 mt-sm-3" onClick={handleButtonClick}>
                <input autocomplete="off" class="form-control shadow-none" id="globalsearchinput" name="q" placeholder="TYPO3 documentation..." type="text" value={searchQuery}></input>
                <button class="btn btn-light"><i class="fa-solid fa-magnifying-glass"></i>&nbsp;<span class="d-none d-md-inline">Search</span></button>
            </div> :
                <button onClick={handleButtonClick} aria-keyshortcuts="Control+K Meta+K" class="btn btn-light d-none d-lg-inline-flex"><i class="fa-solid fa-magnifying-glass"></i>&nbsp;<span class="d-none d-md-inline">Search</span></button>}
        </>
    );
};

export default GlobalSearch;
